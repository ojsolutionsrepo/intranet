---
name: oj-intranet
description: >-
  Builds and changes the OJ Solutions Intranet Portal (Laravel 11 modular
  monolith, Livewire, design system DS-001, Drive broker, Meilisearch ACL,
  phase gates). Use when implementing intranet modules, UI, adapters,
  integrations, auth/RBAC, documents, news, directory, calendar, search,
  projects, or verifying phase gates against URD requirements.
---

# OJ Solutions Intranet Portal

Agent playbook for delivering the intranet. Stakeholder source docs live at repo root (`OJ-INTRA-*`); this skill is the operational contract.

| Doc | Ref | Path |
|-----|-----|------|
| Implementation Plan | OJ-INTRA-IMP-001 | `01_Implementation_Plan (1).md` |
| Phase 6 Implementation Plan | OJ-INTRA-IMP-006 | `docs/phase6/IMPLEMENTATION_PLAN.md` |
| User Requirements | OJ-INTRA-URD-001 | `02_User_Requirements_Document.md` |
| Phase Gates | OJ-INTRA-CHK-001 | `03_Phase_Gate_Verification_Checklist.md` |
| Architecture | OJ-INTRA-ARC-001 | `04_System_Architecture.md` |
| Design System | OJ-INTRA-DS-001 | `05_Design_System.html` |

## Timeline (realistic)

**8–10 weeks** for MVP Must items. Do not compress Phase 5. **Phase 6** is post-MVP (Weeks 11–25) — see [IMP-006](../../../docs/phase6/IMPLEMENTATION_PLAN.md).

| Week | Phase | Focus |
|------|-------|--------|
| 1 | 0 | Foundation, Docker, CI, modules/hooks, auth+MFA, DS shell |
| 2–3 | 1 | Directory, RBAC, import, profiles |
| 4–5 | 2 | News, Documents, Policy hub |
| 6 | 3 | Dashboard, Calendar, Search |
| 7 | 4 | SSO, Drive, Plane, Governex |
| 8–9 | 5 | Hardening, migration, UAT, restore drill, go-live |
| 10 | Buffer | Slippage absorb or early Phase 6.1 spike |
| 11–25 | 6 | Could backlog → Workflow → Experience → LMS → Analytics → Integrations/PWA |

**De-scope ladder** (if weeks slip — never cut Phase 5): Governex → Plane deep-link only → Calendar RSVP/recurrence polish → Org chart → News comments.

**Phase 6 de-scope** (if post-MVP slips): PWA → HR sync → LMS video → Experience packs → keep Workflow + SCH-06. See IMP-006 §12.

## Non-negotiables

1. **Adapter-mediated externals** — modules never import Google/Plane SDKs; depend on interfaces only.
2. **Single authorisation authority** — intranet policies decide access; Drive sharing locked to service account.
3. **Degrade, never fail** — cached data + staleness UI beats error pages.
4. **Modular monolith** — one deployable; hard boundaries under `app/Modules/`.
5. **Audit everything that matters** — access, changes, downloads, permission decisions.
6. **Design tokens only from DS-001** — modules never redefine colours, fonts, or radii.

## Stack

PHP 8.3 · Laravel 11 · Blade + Livewire 3 · Alpine.js · Tailwind · MySQL 8 · Redis 7 · Meilisearch · Horizon · AWS eu-west-2.

**Local development:** XAMPP Apache (see README). Docker Compose is optional for production-parity services only.

## Code standards

- PSR-12 via Pint in CI; PHPStan level 6 (level 8 on `app/Core`)
- Controllers ≤ 15 lines per action; logic in Services
- Form Requests for validation; Policies for authorisation
- Eloquent API Resources for JSON; reversible migrations
- Feature test for every `UR-*` before marking done
- Conventional Commits; PRs link a requirement ID

## Module layout

```
app/
├── Core/          # registry, hooks, RBAC binding, audit — no business logic
├── Modules/       # Directory, Documents, News, Projects, Calendar, Policies, Search, Admin
│                  # Phase 6+: Workflow, Experience, Learning
└── Shared/        # User, Department, Team, AudienceResolver, AuditLogger
```

Each module: `module.json`, ServiceProvider, Models, Http, Services, Livewire, Policies, views, migrations. Disabling a module = `modules.is_enabled = false`; registry short-circuits routes/menu.

## Agent workflow

When implementing any feature:

1. Trace to a `UR-*` ID via [reference/requirements-map.md](reference/requirements-map.md)
2. Confirm phase tasks in [reference/phases.md](reference/phases.md)
3. Follow patterns in [reference/architecture.md](reference/architecture.md)
4. Apply UI from [reference/design-system.md](reference/design-system.md)
5. Place code in the correct `app/Modules/{Name}/`
6. Use shared `AudienceResolver`, Policies, and `AuditLogger` — never ad-hoc authz
7. Externals: adapters only; circuit breaker + staleness indicator required
8. Add Pest feature test covering acceptance criteria
9. Check gate items in [reference/gates.md](reference/gates.md) before claiming phase done
10. Run `php artisan intranet:progress` (optionally `--write`) to validate this skill and update phase progress

## Progress tracking

| Artefact | Purpose |
|----------|---------|
| [progress-checks.json](progress-checks.json) | Declarative repo checks per phase task |
| `php artisan intranet:progress` | Validates SKILL.md sections/references + runs checks |
| `docs/IMPLEMENTATION_PROGRESS.md` | Generated report (`--write`) |

## Open blockers (flag before building)

| # | Item | Blocks |
|---|------|--------|
| 1 | Google Workspace tenant + data region (UK/EU) | Everything Google |
| 2 | Shared Drive provisioned for service account | Documents broker |
| 3 | Governex API vs CSV fallback | Phase 4 driver |
| 4 | Public dashboard SSO: OIDC vs JWT handshake | Phase 4 SSO |
| 5 | Confirmed department list + reporting lines | Phase 1 |
| 6 | Staff headcount + 2-year forecast | Capacity |

## Go-live rule

100% of **Must** URs must be Pass or Pass-with-condition (dated). No Must item may Fail. See [reference/gates.md](reference/gates.md).

## Reference files

- [phases.md](reference/phases.md) — week-by-week tasks and Definition of Done
- [architecture.md](reference/architecture.md) — layers, adapters, broker, schema, queues
- [design-system.md](reference/design-system.md) — tokens, components, Blade/Tailwind
- [requirements-map.md](reference/requirements-map.md) — UR → phase → gate
- [gates.md](reference/gates.md) — Gate 0–6 verification summary
- [Phase 6 IMP](../../../docs/phase6/IMPLEMENTATION_PLAN.md) — OJ-INTRA-IMP-006 post-MVP roadmap
