# Organizational Hierarchy — v1.0 Final Implementation Plan

**Feature:** División / Coordinación hierarchy for project scoping
**Scope:** v1.0 (Phases 1–7). API and reporting deferred to v1.1/v1.2.
**Status:** Ready for implementation — no open questions.

---

## Overview

Additive two-level hierarchy (División → Coordinación) on top of Kimai's existing team model. Three new roles gate visibility: `ROLE_DIRECTOR` (read-only global), `ROLE_LIDER` (own division + teams, union), `ROLE_COORDINADOR` (own coordination). `Project.coordinacion_id` is nullable to preserve all existing data.

All confirmed decisions are fully incorporated. No open questions remain.

### Confirmed Decisions

1. **Team + Org overlap for ROLE_LIDER:** UNION — Líder sees division projects OR team projects (whichever grants more access).
2. **ROLE_DIRECTOR scope:** `view_all_data` only. NOT full system admin. Does NOT inherit `system_configuration` or `ROLE_SUPER_ADMIN` permissions.
3. **Coordinación delete behavior:** BLOCK deletion if any projects are assigned. Admin must reassign all projects first. No SET NULL cascade on delete.
4. **Disabling a División:** Independent — does NOT cascade to Coordinaciones. Show a warning listing how many active Coordinaciones belong to it.
5. **Project creation by Líder/Coordinador in v1:** The Coordinación dropdown IS filtered to the user's own scope. ROLE_COORDINADOR sees only their own Coordinación. ROLE_LIDER sees only Coordinaciones within their División.
6. **API REST:** Out of scope for v1. Planned for v1.1.
7. **Reporting with org filters:** Out of scope for v1. Planned for v1.2.
8. **Líder/Coordinador uniqueness:** One Líder per División (unique index on `division.lider_id`). One Coordinador per Coordinación (unique index on `coordinacion.coordinador_id`).

---

## Risk Register

| # | Risk | Impact | Mitigation |
|---|------|--------|------------|
| 1 | **List/detail divergence** in `ProjectRepository` vs `ProjectVoter` | Critical | Extract `UserOrganizationService::canAccessProject()` as single source of truth; both sides call it |
| 2 | **Unanimous voter strategy** — new voters returning DENY outside their domain break all access | Critical | `supports()` abstains for non-División/Coordinación subjects; unit tests verify ABSTAIN branch |
| 3 | **`view_all_data` short-circuit** — Director must hit early return or gets filtered | High | `canAccessProject()` checks `view_all_data` first; functional test asserts Director sees 100% |
| 4 | **ROLE_DIRECTOR scope creep** — accidentally inheriting admin permissions | High | Director NOT placed above ROLE_ADMIN in hierarchy; functional test asserts 403 on `/admin/system-config/` |
| 5 | **Coordinación delete block** — must be enforced at service layer, not just UI | High | `CoordinacionService::deleteCoordinacion()` throws `CoordinacionDeletionException` if projects exist |
| 6 | **Nullable `coordinacion_id`** — existing projects invisible to scoped roles | Medium | Document in release notes; consider a `kimai:project:show-orphans` command |
| 7 | **User deletion cascade** — `lider_id`/`coordinador_id` become NULL, orphaning entities | Medium | `ON DELETE SET NULL`; admin UI shows "sin responsable" badge |
| 8 | **Unique index conflicts** on Líder/Coordinador reassignment | Medium | Form-level `UniqueEntity` validation with clear error message before persisting |
| 9 | **Empty dropdown (decision 5)** — Coordinador with no active Coordinación sees blank form | Low | Show inline message "No hay Coordinaciones disponibles. Contacte al administrador." |
| 10 | **PHPStan level 9** — nullable types and generic repository annotations | Low | `@extends EntityRepository<Division>`, run `composer phpstan` before each commit |

---

## Phase 1 — Entities, Repositories & Migration (8 h)

**Goal:** Persist División and Coordinación with unique constraints, add nullable FK to Project.

**Files to create:**

- `src/Entity/Division.php` — id, name (unique 100), description (nullable), lider (`ManyToOne → User`, `onDelete=SET NULL`, **unique**), visible (bool). Table `kimai2_divisions`. Indexes: `UNIQ_division_name`, `UNIQ_division_lider`, `IDX_division_visible`.
- `src/Entity/Coordinacion.php` — id, name (100), description (nullable), division (`ManyToOne → Division`, `onDelete=RESTRICT`, not null), coordinador (`ManyToOne → User`, `onDelete=SET NULL`, **unique**), visible. Table `kimai2_coordinaciones`. Indexes: `UNIQ_coord_name_per_division(name, division_id)`, `UNIQ_coord_coordinador`, `IDX_coord_division`, `IDX_coord_visible`.
- `src/Repository/DivisionRepository.php` — `findVisible()`, `findByLider(User): ?Division`, `countActiveCoordinaciones(Division): int`.
- `src/Repository/CoordinacionRepository.php` — `findVisibleByDivision(Division)`, `findByCoordinador(User): ?Coordinacion`, `countProjects(Coordinacion): int`.
- `migrations/Version20260420120000.php` — Creates both tables with unique indexes, adds `coordinacion_id` nullable FK to `kimai2_projects` with `ON DELETE SET NULL`. Fully reversible `down()`.

**Files to modify:**

- `src/Entity/Project.php` — Add `private ?Coordinacion $coordinacion = null;` with `ManyToOne`, nullable, `onDelete=SET NULL`, getter/setter.

**Acceptance criteria:**

- `composer linting` passes schema check.
- Migration runs up and down cleanly on MySQL 8.3 with populated data.
- `UNIQ_division_lider` prevents two Divisions with the same `lider_id`.
- `UNIQ_coord_coordinador` prevents same user as Coordinador of two Coordinations.
- Existing projects have `coordinacion_id = NULL` after migration.
- PHPStan level 9 passes on both new entities and repositories.

---

## Phase 2 — Permissions, Voters & Role Hierarchy (6 h)

**Goal:** Define three new roles with curated permissions; voters abstain outside their domain.

**Files to create:**

- `src/Voter/DivisionVoter.php` — Attributes: `view`, `edit`, `delete`, `list`. `supports()` returns false for non-División subjects or unrecognized attributes. ROLE_DIRECTOR: `view`/`list` only. ROLE_LIDER: `view` own División only.
- `src/Voter/CoordinacionVoter.php` — Same pattern. ROLE_LIDER views all Coordinations in own División. ROLE_COORDINADOR views own only.

**Files to modify:**

- `config/packages/security.yaml` — Role hierarchy (**ROLE_DIRECTOR does NOT go above ROLE_ADMIN**, decision 2):
  ```yaml
  ROLE_DIRECTOR:    [ROLE_USER]
  ROLE_LIDER:       [ROLE_TEAMLEAD]
  ROLE_COORDINADOR: [ROLE_USER]
  ```
- `config/packages/kimai.yaml` — New permission sets:
  - `DIRECTOR`: `view_all_data`, `view_other_timesheet`, `view_rate_other_timesheet`, `view_export`, `view_reporting`, `view_team`, `view_project`, `view_customer`, `view_activity`. **Explicitly excludes** `system_configuration`, `user_management`, `plugins`.
  - `LIDER`: `view_team`, `edit_team`, `view_project`, `create_project`, `edit_project`, `view_customer`.
  - `COORDINADOR`: `view_project`, `create_project`, `edit_project_assigned`, `view_customer`.

**Acceptance criteria:**

- Functional test: ROLE_DIRECTOR → 403 on `/admin/system-config/` and `/admin/user/`.
- Functional test: ROLE_DIRECTOR → 200 on `/admin/reporting/`.
- Unit tests: both voters return `ACCESS_ABSTAIN` for unrecognized subjects/attributes.
- `composer linting` and `composer phpstan` pass.

---

## Phase 3 — División Admin CRUD (10 h)

**Goal:** ROLE_ADMIN can manage Divisions; toggling visibility does NOT cascade to Coordinations (decision 4) — UI shows warning instead.

**Files to create:**

- `src/Controller/DivisionController.php` — Routes `/admin/division`, `#[IsGranted('ROLE_ADMIN')]`. Actions: index, create, edit, delete, toggleVisible.
- `src/Form/DivisionEditForm.php` — name, description, lider (UserType filtered to users NOT already assigned as Líder in another División), visible. `UniqueEntity` on name and lider.
- `src/Division/DivisionService.php` — `createDivision()`, `saveDivision()`, `deleteDivision()` (blocks if has Coordinations).
- `templates/division/index.html.twig` — Columns: name, líder, # active Coordinations, visible.
- `templates/division/edit.html.twig` — Includes warning block when toggling visible to false: _"Al ocultar esta División NO se ocultarán sus N Coordinaciones hijas. Debe gestionarlas por separado."_
- `templates/division/_confirm_delete.html.twig` — Shows # child Coordinations; blocks delete if > 0.

**Files to modify:**

- `src/EventSubscriber/MenuSubscriber.php` — Add "Divisiones" menu entry under admin, guarded by `is_granted('ROLE_ADMIN')`.
- `translations/messages.en.xliff` / `translations/messages.es.xliff` — Add division keys including `division.toggle_warning`.

**Acceptance criteria:**

- Full CRUD works for ROLE_ADMIN.
- ROLE_DIRECTOR receives 403 on POST to `/admin/division/create`.
- Toggling visibility to false on a División with 3 active Coordinations: warning shown, all 3 Coordinations keep `visible = true` (integration test).
- Delete with child Coordinations shows blocking modal, does not delete.
- Form rejects a User already assigned as Líder of another active División.

---

## Phase 4 — Coordinación Admin CRUD (8 h)

**Goal:** ROLE_ADMIN manages Coordinations; delete is **blocked** (not SET NULL) when projects are assigned (decision 3).

**Files to create:**

- `src/Controller/CoordinacionController.php` — Routes `/admin/coordinacion`, ROLE_ADMIN required.
- `src/Form/CoordinacionEditForm.php` — name, description, division (EntityType, required), coordinador (UserType, not already assigned to another Coordinación), visible.
- `src/Coordinacion/CoordinacionService.php` — `deleteCoordinacion(Coordinacion): void` throws `CoordinacionDeletionException` if `countProjects() > 0`.
- `src/Coordinacion/CoordinacionDeletionException.php` — Extends `\RuntimeException`, exposes `getProjectCount(): int`.
- `templates/coordinacion/index.html.twig` — Columns: name, división, coordinador, # projects, visible.
- `templates/coordinacion/edit.html.twig`
- `templates/coordinacion/_confirm_delete.html.twig` — Hides delete button and shows "Debe reasignar N proyectos antes de eliminar esta Coordinación" when count > 0.

**Files to modify:**

- `src/EventSubscriber/MenuSubscriber.php` — Add "Coordinaciones" entry below "Divisiones".
- `translations/messages.en.xliff` / `translations/messages.es.xliff` — Add `coordinacion.delete_blocked`, `coordinacion.projects_count`.

**Acceptance criteria:**

- Delete Coordination with 0 projects: deletes correctly.
- Delete Coordination with ≥1 project: does NOT delete, returns flash error with count; entity persists in DB. Integration test: create Coordination + 2 projects, attempt delete, assert Coordination still exists.
- Template hides delete button and shows reassignment notice when projects exist.
- Form rejects User already assigned as Coordinador of another Coordinación.

---

## Phase 6 — Scoping Project Listings & Voter with Union (7 h)

> **This phase is implemented BEFORE Phase 5** because `CoordinacionType` (Phase 5) depends on `UserOrganizationService` created here.

**Goal:** Apply org scoping to project listings and the project voter. ROLE_LIDER uses UNION (org OR teams, decision 1). Single service is the source of truth to prevent list/detail divergence.

**Files to create:**

- `src/User/UserOrganizationService.php`
  - `getDivision(User): ?Division`, `getCoordinacion(User): ?Coordinacion`.
  - `canAccessProject(User, Project): bool` — single source of truth:
    - `view_all_data` → `true` (short-circuit, decision 2 + risk 3).
    - ROLE_LIDER → `project.coordinacion.division === getDivision($user)` **OR** `userIsInProjectTeam($user, $project)` (decision 1 UNION — intentional, not a bug).
    - ROLE_COORDINADOR → `project.coordinacion === getCoordinacion($user)`.
    - Others → existing team logic unchanged.

**Files to modify:**

- `src/Repository/ProjectRepository.php` — Extend `getPermissionCriteria()`:
  - Short-circuit for `view_all_data` (unchanged).
  - ROLE_LIDER: `(p.coordinacion IN (:lider_coords)) OR (p IN (:team_projects))`. Add inline comment: _"UNION intencional: Líder ve su División O proyectos donde esté en team — ver spec/01_organizacion_divisiones.md decision 1"_.
  - ROLE_COORDINADOR: strict `p.coordinacion = :user_coordinacion`.
- `src/Voter/ProjectVoter.php` — Delegate to `UserOrganizationService::canAccessProject()` in `voteOnAttribute()` alongside existing team checks.

**Acceptance criteria:**

- Integration test: Líder of División A sees projects from División A, not División B.
- Integration test (UNION, decision 1): project in División B belonging to a Team the Líder is in → Líder sees it (3 projects total, not 2).
- Integration test: Director sees all projects (no filter).
- Integration test: Coordinador sees only their own Coordination's projects.
- `UserOrganizationService::canAccessProject()` returns identical results to the query builder for all role combinations (parameterized test).
- `composer phpstan` passes.

---

## Phase 5 — Project Form Integration with Scoped Dropdown (4 h)

> **Depends on Phase 6** — `UserOrganizationService` must exist before this phase starts.

**Goal:** Add grouped, role-scoped Coordinación dropdown to the project edit form (decision 5).

**Files to create:**

- `src/Form/Type/CoordinacionType.php` — EntityType-based form type. Injects `Security` and `UserOrganizationService`. `query_builder` filters by role:
  - `view_all_data` → all visible Coordinations.
  - ROLE_LIDER → `findVisibleByDivision($orgService->getDivision($user))`.
  - ROLE_COORDINADOR → `findVisibleByCoordinador($user)` (returns only their own).
  - `group_by` groups by División name (follows `ActivityType` pattern, creates `<optgroup>`).
  - `placeholder = 'label.none'` (FK nullable).

**Files to modify:**

- `src/Form/ProjectEditForm.php` — Add `coordinacion` field (`CoordinacionType`, `required: false`).
- `templates/project/edit.html.twig` — Render new field near `customer`. Show inline message _"No hay Coordinaciones disponibles. Contacte al administrador."_ if query returns empty.
- `src/Controller/ProjectController.php` — Verify ROLE_COORDINADOR and ROLE_LIDER pass `create_project` check.

**Acceptance criteria:**

- Admin sees all active Coordinations grouped by División (`<optgroup>`).
- ROLE_LIDER sees only Coordinations from their División.
- ROLE_COORDINADOR sees only their own Coordinación (pre-selected).
- Saving without a Coordinación is valid (nullable FK).
- Unit test on `CoordinacionType` mocking `Security` and `UserOrganizationService` for each role.

---

## Phase 7 — Tests & Documentation (5 h)

**Goal:** Close testing gaps and document the feature for future developers.

**Files to create:**

- `tests/Entity/DivisionTest.php` (unit)
- `tests/Entity/CoordinacionTest.php` (unit)
- `tests/Repository/DivisionRepositoryTest.php` (integration group)
- `tests/Repository/CoordinacionRepositoryTest.php` (integration group)
- `tests/Voter/DivisionVoterTest.php`
- `tests/Voter/CoordinacionVoterTest.php`
- `tests/Controller/DivisionControllerTest.php` (functional)
- `tests/Controller/CoordinacionControllerTest.php` (functional)
- `tests/User/UserOrganizationServiceTest.php`
- `tests/Form/Type/CoordinacionTypeTest.php`
- `tests/Coordinacion/CoordinacionServiceTest.php` (covers delete block, decision 3)

**Files to modify:**

- `translations/messages.en.xliff` — Complete review of all new keys.
- `translations/messages.es.xliff` — Full Spanish strings for all user-facing text.
- `CLAUDE.md` — Add "Organizational Hierarchy" section documenting new entities, roles, and that ROLE_DIRECTOR is read-only (no write access).

**Acceptance criteria:**

- `composer tests-unit` green.
- `composer tests-integration` green.
- `composer phpstan` level 9 green.
- `composer linting` green.
- `composer code-check` (full gate) green.
- Coverage on new files ≥85%.

---

## Dependency Map

```
Phase 1 (Entities + Migration)
    └── Phase 2 (Permissions + Voters)
            ├── Phase 3 (División CRUD)
            ├── Phase 4 (Coordinación CRUD)
            └── Phase 6 (Scoping + UserOrganizationService)
                    └── Phase 5 (Project Form + CoordinacionType)
                                └── Phase 7 (Tests + Docs)
```

**Recommended implementation order:** 1 → 2 → 3 → 4 → **6 → 5** → 7

> Phases 3 and 4 can be parallelized by two developers after Phase 2 completes.

---

## Total Estimated Hours

| Phase | Description | Hours |
|-------|-------------|-------|
| 1 | Entities, Repositories & Migration | 8 |
| 2 | Permissions, Voters & Role Hierarchy | 6 |
| 3 | División Admin CRUD | 10 |
| 4 | Coordinación Admin CRUD | 8 |
| 6 | Scoping Listings + Voter (UNION) | 7 |
| 5 | Project Form + Scoped Dropdown | 4 |
| 7 | Tests & Documentation | 5 |
| **Total v1.0** | | **48 h** |
| **With 15% contingency** | | **~55 h** |

---

## v1.1 / v1.2 Backlog

### v1.1 — REST API (~10–12 h)

- GET/POST/PATCH/DELETE `/api/divisions` and `/api/coordinaciones`.
- Extend `/api/projects` with `coordinacion_id` and `division_id` filter params.
- Embed `coordinacion` in Project resource response.
- Update OpenAPI spec (NelmioApiDocBundle).
- Reuses `UserOrganizationService` for access control.

### v1.2 — Reporting with Org Filters (~12–16 h)

- Dashboard widgets grouped by División / Coordinación.
- División and Coordinación filters in `/admin/reporting/*`.
- Export columns (PDF/CSV/Spreadsheet) for División and Coordinación.
- Dedicated "Horas por División" and "Horas por Coordinación" reports for ROLE_DIRECTOR.
- All queries respect `view_all_data` short-circuit.

---

## Key Reference Files

For the developer implementing this plan, the primary Kimai patterns to follow are:

| File | Used as pattern for |
|------|---------------------|
| `src/Entity/Project.php` | Entity structure |
| `src/Entity/Customer.php` | Entity with toggle visibility |
| `src/Repository/ProjectRepository.php` | Permission scoping in query builder |
| `src/Repository/CustomerRepository.php` | Repository structure |
| `src/Voter/ProjectVoter.php` | Voter structure and unanimous strategy |
| `src/Controller/CustomerController.php` | Admin CRUD controller pattern |
| `src/Form/ProjectEditForm.php` | Form structure |
| `src/Form/Type/ActivityType.php` | `group_by` pattern for grouped dropdowns |
| `src/EventSubscriber/MenuSubscriber.php` | Adding menu entries |
| `config/packages/kimai.yaml` | Permission sets and role maps |
| `config/packages/security.yaml` | Role hierarchy |
| `migrations/` (any recent file) | Migration format and base class |
