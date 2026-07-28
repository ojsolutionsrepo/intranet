# Phased Implementation (realistic 8–10 weeks)

Supersedes the 21-day calendar in OJ-INTRA-IMP-001 for delivery planning. Task content and DoD still map to that plan and to gates in `gates.md`.

## Phase 0 — Foundation (Week 1) → Gate 0

**Objective:** deployable empty shell with module system, auth, CI, design-system shell.

| # | Task | Output |
|---|------|--------|
| 0.1 | Laravel 11 skeleton; Pint, PHPStan, Pest | Repo ready |
| 0.2 | XAMPP Apache DocumentRoot / vhost (`apache/vhost.conf`); Docker Compose optional | App at `http://localhost/intranet` or `http://intranet.local` |
| 0.3 | `ModuleRegistry`, loader, test module toggle | Sidebar + routes gated |
| 0.4 | `Hook::action` / `Hook::filter` | HookManager |
| 0.5 | spatie/permission; seed Admin / Manager / Staff / Guest | RoleSeeder |
| 0.6 | Fortify: email+password, idle timeout, Admin TOTP, password reset 60 min | Auth flows |
| 0.7 | App shell: sidebar, header, breadcrumb, flash — **DS-001 tokens in Tailwind** | `layouts/app.blade.php` |
| 0.8 | `AuditLogger` + Auditable trait | `audit_logs` |
| 0.9 | `settings` + cached Settings facade | Config stub |
| 0.10 | CI: Pint → PHPStan → Pest → staging deploy | Green on `main` |
| 0.11 | Staging HTTPS + daily RDS snapshot | Live staging URL |

**DoD:** CI green; deliberate PSR-12 fail blocks merge; Admin MFA login; Staff 403 on `/admin`; dummy module enable/disable; audit records login.

---

## Phase 1 — Identity, RBAC & Directory (Weeks 2–3) → Gate 1

**Objective:** every staff member exists, is in a department, and is findable (FR-4 + FR-7 partial).

**Build order:** departments/teams schema → user import → profiles → directory UI → department pages → admin CRUD → permission matrix → org chart (Should).

| # | Task | UR |
|---|------|-----|
| 1.1 | Departments (parent_id), teams, pivots, seeders | DIR-01 |
| 1.2 | CSV/XLSX import: validate → preview → commit | DIR-09 |
| 1.3 | Profiles: photo (400px WebP + 96px thumb), expertise tags, bio, phone, etc. | DIR-04 |
| 1.4 | Directory index: card/list, filters, Livewire | DIR-02, 03 |
| 1.5 | Profile detail + reporting line | DIR-04, 07 |
| 1.6 | Department detail page | DIR-05 |
| 1.7 | Org chart (collapsible) — Should | DIR-07 |
| 1.8 | Self-service profile edit (not role/dept/title) | DIR-06 |
| 1.9 | Admin user CRUD; deactivate kills session ≤ 60s | ADM-01, AUT-06 |
| 1.10 | Permission matrix (roles × permissions) | ADM-02, 03 |

**DoD:** Full staff imported, zero without department; typo-tolerant name search < 2s; Staff 403 editing others; all writes in audit log. Persona: Jasmine finds dept, skill colleague, extension < 90s.

---

## Phase 2 — News, Documents, Policy (Weeks 4–5) → Gate 2

**Objective:** replace email announcements and scattered Drive (FR-2, FR-3, Policy hub).

### News build order

Model + TipTap/HTMLPurifier → pin/schedule → AudienceResolver → feed + alert banner → read tracking → workflow Draft/In Review/Published → digest job → comments (Could).

### Documents build order (highest risk)

1. Storage abstraction (`DocumentStorageAdapter` + Local/S3; Drive driver Phase 4)
2. Nested categories + breadcrumbs
3. Versioning + restore-as-new-version
4. SHA-256 duplicate warning + named owner
5. DocumentPolicy ACL (category default + override)
6. Preview (PDF.js / LibreOffice PDF cache)
7. Queued text extraction → Meilisearch index later
8. Multi-upload, trash 30-day, download audit

### Policy hub

View over documents where `is_policy = true`: acknowledgement per version, compliance matrix, review reminders 30/14/7, status chips.

**Critical demos:** version cycle v1→v2→download v1→restore; PDF body search; Dept A vs B ACL on list/search/URL; spoofed extension rejected; acknowledgement resets on new version.

**DoD:** Gate 2A–2C Must items pass. Persona: Debby publishes mandatory policy, targets two depts, exports acknowledgement report unaided.

---

## Phase 3 — Dashboard, Calendar, Search (Week 6) → Gate 3

**Objective:** tie modules together (FR-1, FR-6, Search).

| Area | Build order |
|------|-------------|
| Dashboard | Widget registry → lazy Livewire widgets → per-user layout prefs → skeleton < 1.5s shell |
| Calendar | Event CRUD + AudienceResolver → FullCalendar views → ICS download/feed → recurrence (Should) → RSVP (Could) |
| Search | Scout + Meilisearch → ACL filter at query time → omnibox Cmd/Ctrl+K → facets → synonyms → zero-result log |

MVP widgets: Announcements · My Documents · Upcoming Events · My Projects · Quick Links · Outstanding acknowledgements · New joiners (Could).

**Instrument analytics events now** (needed for PRD success metrics later).

**DoD:** Two-user differential search; search p95 < 2s on ≥5k docs; ICS imports to Outlook/Google; failed widget does not break page. Persona: Seyitan finds unknown template via search < 60s.

---

## Phase 4 — Integrations (Week 7) → Gate 4

**Pattern for every integration:** Adapter → driver → queued sync → local mirror → circuit breaker → admin health page.

| Integration | Approach | Fallback |
|-------------|----------|----------|
| Dashboard SSO | OIDC preferred; else signed JWT 60s + jti | Local login always on |
| Google Drive | Broker (preferred) or mirror/migrate per category | Native storage; Drive read-only |
| Plane.so | Poll 15 min + webhook if available | Manual projects + staleness badge |
| Governex | API or CSV via same adapter | Manual entry |
| Projects UI | Unified list/detail, RAG, deep link, RBAC | — |

**DoD:** Kill each external in turn — core stays up with staleness UI; SSO round-trip both ways; Plane project visible ≤ 15 min; no secrets in repo.

---

## Phase 5 — Hardening & Go-Live (Weeks 8–9) → Gate 5

**Do not compress.** Parallel tracks: security · performance/a11y · data migration · UAT · ops · adoption.

| Track | Must prove |
|-------|------------|
| Security | OWASP clean of critical/high; every route has policy; IDOR suite; rate limits; headers; ClamAV |
| Perf / a11y | k6 100 concurrent p95 < 3s; search < 2s; axe-core; keyboard + NVDA on 5 flows |
| Migration | Taxonomy signed; dry-run 10% sample; checksum reconciliation; rollback plan |
| Resilience | Restore drill within RTO 4h; PITR; monitoring + tested on-call alert |
| UAT | 8+ testers, 5 personas, all P1 closed |
| Adoption | Training, guides, champions, hypercare channel, launch comms |

**DoD:** Sponsor + PM sign URD checklist; zero open Must fails; restore drill evidenced.

---

## Week 10 — Buffer / hypercare prep

Absorb slippage. If on track: seed Phase 6.1 workflow engine spike, or deepen hypercare runbooks. Event capture from Phase 3 remains the measurement baseline.

## Phase 6 (post-MVP, Weeks 11–25) → Gate 6

**Out of MVP scope.** Full plan: [`docs/phase6/IMPLEMENTATION_PLAN.md`](../../../../docs/phase6/IMPLEMENTATION_PLAN.md) (OJ-INTRA-IMP-006).

**Prerequisite:** Gate 5 Pass or Pass-with-dated-condition. Week 10 may only seed 6.1 workflow spike or hypercare — not LMS/PWA.

| Sub-phase | Weeks | Focus | Key URs |
|-----------|------:|-------|---------|
| 6.0 | 11–12 | Could backlog | DIR-10, NEW-09, CAL-06, SCH-06 |
| 6.1 | 11–14 | Workflow engine + News approval consumer | WFL-01…04, NEW-10 |
| 6.2 | 14–17 | Employee experience / journeys | EXP-01…05 |
| 6.3 | 17–20 | LMS lite (`LearningProvider` local first) | LMS-01…05 |
| 6.4 | 18–21 | Analytics dashboard over `Analytics` events | ANL-01…04 |
| 6.5 | 21–24 | Zenzap, HR/payroll, PWA | INT-07/08, PWA-01/02 |

**Build order:** Could footholds → Workflow module → Experience (reuses Workflow) → Learning → Admin analytics → adapters + PWA.

**Modules:** `Workflow`, `Experience`, `Learning` under `app/Modules/`; analytics UI in Admin; adapters in `app/Shared/`.

**De-scope ladder:** PWA → HR sync → LMS video → Experience packs → keep Workflow + SCH-06.

**DoD:** Gate 6 tracks in IMP-006 §13; Pest per new `UR-*`; degrade-never-fail on new integrations.
