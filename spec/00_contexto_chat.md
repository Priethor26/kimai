# Contexto para nuevo chat con Claude.ai

Copia y pega esto al inicio de cada nueva sesión:

---

Somos un equipo trabajando en una personalización de Kimai 
(software de control de tiempos open source, licencia MIT).

## Ambiente
- OS: Windows 11 + WSL2 Ubuntu 22.04
- Stack: PHP 8.2 + Symfony, MySQL 8.3, Docker Desktop
- Proyecto en: ~/proyectos/kimai
- URL local: http://localhost:8001
- IDE: VS Code + extensión WSL
- Claude Code CLI instalado

## Repositorio
- github.com/Priethor26/kimai
- main (producción) → dev (integración) → feature/nombre

## Rama activa
- feature/division-proyecto

## Agentes Claude Code disponibles
- .claude/agents/architect.md → análisis, NO escribe código
- .claude/agents/developer.md → implementación por fases

## Spec del proyecto activo
- spec/01_organizacion_divisiones.md → plan completo de implementación
- spec/00_contexto_chat.md → este archivo

## Lo que estamos construyendo
Jerarquía organizacional sobre Kimai:
Director → División → Coordinación → Proyecto

- División: tiene un Líder (usuario Kimai)
- Coordinación: pertenece a una División, tiene un Coordinador
- Proyecto: se asigna a una Coordinación (hereda la División)

## Roles nuevos
- ROLE_DIRECTOR → ve todo (view_all_data)
- ROLE_LIDER → ve su división + equipos (unión)
- ROLE_COORDINADOR → ve solo su coordinación

## Decisiones clave ya tomadas
- Cambios en src/ (no plugins — limitación de Doctrine)
- Coordinación NO se puede eliminar si tiene proyectos
- Deshabilitar División no cascadea a Coordinaciones
- Dropdown filtra por scope del usuario en v1
- API REST → v1.1 | Reportes → v1.2

## Estado de implementación
- [x] Phase 1 — Entities, Repositories & Migration ✅
- [ ] Phase 2 — Permissions, Voters & Role Hierarchy
- [ ] Phase 3 — Division Admin CRUD
- [ ] Phase 4 — Coordinacion Admin CRUD
- [ ] Phase 5 — Project Form Integration
- [ ] Phase 6 — Scoping Project Listings & Voter
- [ ] Phase 7 — Tests & Documentation

## Perfil del desarrollador
Soy desarrollador novato en aprendizaje.
Guíame paso a paso con buenas prácticas.
No asumas conocimiento previo — explica el "por qué".