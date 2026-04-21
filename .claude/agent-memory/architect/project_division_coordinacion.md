---
name: Division/Coordinacion feature scope
description: Ongoing feature adding org hierarchy (Director → Division → Coordinacion → Project) to Kimai on branch feature/division-proyecto
type: project
---

Branch `feature/division-proyecto` adds an organizational layer above Kimai projects:
- New entities Division (lider_id → User) and Coordinacion (division_id, coordinador_id → User).
- Project gains nullable `coordinacion_id` FK.
- Three new roles: ROLE_DIRECTOR (all), ROLE_LIDER (own division), ROLE_COORDINADOR (own coordinacion).

**Why:** The IT area is imposing this hierarchy on top of Kimai; it is additive, not replacing teams.

**How to apply:** Treat this as a core change (not a plugin) — plugins cannot alter core Doctrine mappings. Keep PHP identifiers English, DB column names Spanish (`nombre`, `estado`, `lider_id`, `coordinador_id`). When changing ProjectRepository permission criteria, also update ProjectVoter in lockstep to avoid list/detail access divergence. Kimai uses security strategy `unanimous`, so new voters must ABSTAIN outside their domain, never DENY.
