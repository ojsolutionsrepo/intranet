# Documentation index

Technical and operational documentation for the OJ Solutions Intranet Portal.

## System (as built)

| Document | Audience | Contents |
|----------|----------|----------|
| [TECHNICAL.md](TECHNICAL.md) | Engineers, architects | As-built architecture, modules, security, data, ops |
| [DEVELOPER.md](DEVELOPER.md) | Engineers adding features | How to add a module, conventions, tests |
| [FLOWS.md](FLOWS.md) | Engineers | Sequence diagrams for login, ACL, documents, staff info, sync |
| [ROUTES.md](ROUTES.md) | Engineers | Named HTTP routes, middleware, permissions |

## Operations and go-live

| Document | Contents |
|----------|----------|
| [ops/deploy-runbook.md](ops/deploy-runbook.md) | Local and production deploy |
| [ops/restore-drill-template.md](ops/restore-drill-template.md) | Gate 5D restore drill |
| [security/headers-and-rate-limits.md](security/headers-and-rate-limits.md) | CSP, HSTS, rate limits |
| [migration/taxonomy.md](migration/taxonomy.md) | Content migration map |
| [migration/rollback.md](migration/rollback.md) | DB/storage rollback |
| [adoption/hypercare.md](adoption/hypercare.md) | Training and hypercare |
| [uat/sign-off-checklist.md](uat/sign-off-checklist.md) | UAT sign-off |

## Product specs (repo root)

| ID | File |
|----|------|
| OJ-INTRA-IMP-001 | `01_Implementation_Plan (1).md` |
| OJ-INTRA-URD-001 | `02_User_Requirements_Document.md` |
| OJ-INTRA-CHK-001 | `03_Phase_Gate_Verification_Checklist.md` |
| OJ-INTRA-ARC-001 | `04_System_Architecture.md` |
| OJ-INTRA-DS-001 | `05_Design_System.html` |
| OJ-INTRA-IMP-006 | [phase6/IMPLEMENTATION_PLAN.md](phase6/IMPLEMENTATION_PLAN.md) |

Local setup: repository [`README.md`](../README.md). Agent playbook: [`.cursor/skills/oj-intranet/SKILL.md`](../.cursor/skills/oj-intranet/SKILL.md).
