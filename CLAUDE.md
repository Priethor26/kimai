# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## About Kimai

Kimai is an open-source time-tracking application built with PHP 8.1+, Symfony 6.4, Doctrine ORM, and Bootstrap 5 / Tabler UI (Webpack Encore on the frontend). It supports multi-user, multi-timezone, multi-language setups, a plugin system, REST API, and multiple auth methods (database, LDAP, SAML).

## Commands

### PHP / Backend

```bash
composer tests               # Run all tests
composer tests-unit          # Unit tests only (exclude integration group)
composer tests-integration   # Integration tests only
composer linting             # Lint container, YAML, Twig, DB schema, XLIFF translations
composer codestyle           # Check PHP-CS-Fixer rules (dry-run)
composer codestyle-fix       # Auto-fix code style
composer phpstan             # Static analysis (src + tests), level 9
composer pre-commit          # codestyle + phpstan + linting + tests-unit
composer code-check          # pre-commit + tests-integration
```

Run a single test file or method:
```bash
vendor/bin/phpunit tests/Path/To/SomeTest.php
vendor/bin/phpunit tests/Path/To/SomeTest.php::testMethodName
```

Test config: `phpunit.xml.dist`. Integration tests require a MySQL database (`kimai2_test` at `127.0.0.1:3306`).

### Frontend / Assets

```bash
yarn build       # Production build
yarn dev         # Development build
yarn watch       # Watch mode
yarn lint        # ESLint on assets/js/
```

## Architecture

### Directory Layout

| Path | Purpose |
|------|---------|
| `src/` | All application PHP code |
| `tests/` | Mirrors `src/` structure |
| `templates/` | Twig templates |
| `assets/` | JS/CSS sources (compiled by Webpack) |
| `config/` | Symfony config, routes, services |
| `var/plugins/` | Installed plugins (PSR-4: `KimaiPlugin\\`) |
| `migrations/` | Doctrine database migrations |
| `translations/` | XLIFF translation files |

### Key `src/` Modules

- **`Timesheet/`** — core time-tracking domain (rate calculators, rounding, locking rules)
- **`Activity/`, `Project/`, `Customer/`** — the three main business entities
- **`Entity/`** — Doctrine entities; `Repository/` holds their query logic
- **`API/`** — REST endpoints (FriendsOfSymfony REST Bundle + NelmioApiDocBundle)
- **`Controller/`** — Web controllers; all extend `AbstractController`
- **`Form/`** — Symfony form types
- **`EventSubscriber/`, `Event/`** — event-driven extension points used heavily by plugins
- **`Export/`, `Invoice/`, `Pdf/`** — business output services registered via compiler passes
- **`Security/`, `Voter/`** — permission/authorization layer
- **`Plugin/`** — plugin loader; plugins live in `var/plugins/` and implement `PluginInterface`
- **`Ldap/`, `Saml/`** — alternative authentication providers
- **`Command/`** — Symfony Console commands

### Extension Points

New export renderers, invoice calculators, widgets, and similar services are discovered via **compiler passes** in `src/DependencyInjection/Compiler/`. Tag your service with the appropriate tag rather than wiring it manually.

### Code Style & Static Analysis

- PHP-CS-Fixer enforces 160+ rules (including AGPL file-header comment).
- PHPStan runs at **level 9** with Symfony and Doctrine extensions; see `phpstan.neon`.
- Exclusions: `Ldap/LdapDriver.php` and `Event/PageActionsEvent.php` are excluded from PHPStan.
- `composer pre-commit` is the expected gate before pushing.

# CLAUDE.md — Contexto del Proyecto Kimai

## Proyecto
Software de control de tiempos Kimai, adaptado para el área de IT.
Repositorio: github.com/Priethor26/kimai

## Ambiente de desarrollo
- OS: Windows 11 + WSL2 Ubuntu 22.04
- Stack: PHP 8.2 + Symfony, MySQL 8.3, Apache puerto 8001
- Docker Desktop con integración WSL2
- Proyecto en: ~/proyectos/kimai

## Correr el ambiente
1. Iniciar Docker Desktop en Windows
2. cd ~/proyectos/kimai
3. docker compose up -d
4. http://localhost:8001
5. admin@kimai.local / Admin1234!

## Estructura de ramas
- main → producción
- dev → integración
- feature/nombre → desarrollo de features

## Agentes disponibles
- .claude/agents/architect.md → análisis e impacto, NO escribe código
- .claude/agents/developer.md → implementación por fases

## Especificaciones del proyecto
Todas las specs viven en spec/:
- spec/01_organizacion_divisiones.md → feature actual en desarrollo

## Feature en desarrollo
**feature/division-proyecto** — Estructura organizacional IT

Agrega jerarquía: Director → División → Coordinación → Proyecto

### Modelo de datos
- División: id, nombre, estado, lider_id (→ User)
- Coordinación: id, nombre, estado, division_id, coordinador_id (→ User)
- Proyecto: agrega coordinacion_id (nullable, → Coordinacion)

### Roles
- ROLE_DIRECTOR → ve todo
- ROLE_LIDER → ve su división
- ROLE_COORDINADOR → ve su coordinación

### Decisiones clave tomadas
- Cambios en src/ (no plugins — limitación de Doctrine)
- Coordinación no se puede eliminar si tiene proyectos asignados
- Deshabilitar División no cascadea a Coordinaciones
- Dropdown de coordinación filtra por scope del usuario en v1
- API REST → v1.1 (fuera de scope actual)
- Reportes → v1.2 (fuera de scope actual)
- Un Líder = una División (índice único)

### Versiones planificadas
- v1.0 → Estructura base + CRUD + permisos (en desarrollo)
- v1.1 → API REST para División y Coordinación
- v1.2 → Reportes filtrados por jerarquía

### Estado actual
- [x] Spec creado: spec/01_organizacion_divisiones.md
- [x] Agentes definidos: architect.md, developer.md
- [ ] Phase 1 — Entities, Repositories & Migration (8h)
- [ ] Phase 2 — Permissions, Voters & Role Hierarchy (6h)
- [ ] Phase 3 — Division Admin CRUD (10h)
- [ ] Phase 4 — Coordinacion Admin CRUD (8h)
- [ ] Phase 5 — Project Form Integration (4h)
- [ ] Phase 6 — Scoping Project Listings & Voter (7h)
- [ ] Phase 7 — Tests & Documentation (5h)

## Regla crítica de desarrollo
Al modificar ProjectRepository y ProjectVoter siempre
hacerlo en el mismo commit — deben reflejar las mismas
reglas de scoping en todo momento.

## Comandos útiles
```bash
# Verificar contenedores activos
docker compose ps

# Logs del contenedor PHP
docker compose logs -f php

# Entrar al contenedor PHP
docker compose exec php bash

# Correr migraciones
php bin/console doctrine:migrations:migrate

# Verificar esquema
php bin/console doctrine:schema:validate
```