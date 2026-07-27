# Requirements Map (OJ-INTRA-URD-001)

Priority: **M** = Must (MVP blocking) · **S** = Should · **C** = Could (post-MVP / stretch).

Go-live: 100% of Must items Pass or Pass-with-dated-condition. No Must may Fail.

When implementing, cite the `UR-*` ID in the PR and write a Pest feature test against the acceptance criteria in `02_User_Requirements_Document.md`.

---

## Must requirements

### Authentication (AUT) — Phases 0, 1, 4

| Ref | Summary | Phase | Gate |
|-----|---------|:-----:|------|
| UR-AUT-01 | Company credentials / SSO + local fallback | 0, 4 | 0.10, 4.1, 4.4 |
| UR-AUT-02 | Session persists dashboard ↔ intranet | 4 | 4.1, 4.2 |
| UR-AUT-03 | Admin MFA (TOTP) | 0 | 0.11 |
| UR-AUT-04 | Idle session timeout + destination preserved | 0 | 0.12 |
| UR-AUT-05 | Password reset 60 min single-use | 0 | 0.10 |
| UR-AUT-06 | Deactivate kills sessions ≤ 60s; content kept | 1 | 1.18, 1.19 |
| UR-AUT-07 | Entitlement enforced on list, search, direct URL | 1–3 | 1.13, 2.6, 2.20, 3.12, 3.17 |

### Directory (DIR) — Phase 1

| Ref | Summary | Phase | Gate |
|-----|---------|:-----:|------|
| UR-DIR-01 | Browse departments + sub-teams | 1 | 1.1, 1.2 |
| UR-DIR-02 | Name search < 2s, 1-char typo | 1 | 1.5, 1.6 |
| UR-DIR-03 | Filter dept / role / expertise (combinable) | 1 | 1.7, 1.8 |
| UR-DIR-04 | Full profile (10 fields) | 1 | 1.9 |
| UR-DIR-05 | Department page content | 1 | 1.10 |
| UR-DIR-06 | Self-edit bio/photo/phone/location/expertise only | 1 | 1.11, 1.12 |
| UR-DIR-09 | CSV/XLSX import with preview + reject bad rows | 1 | 1.3, 1.4 |

### News (NEW) — Phase 2

| Ref | Summary | Phase | Gate |
|-----|---------|:-----:|------|
| UR-NEW-01 | Feed: title, summary, author, date, category, infinite scroll | 2 | 2.1, 2.2 |
| UR-NEW-02 | Pin critical posts | 2 | 2.3 |
| UR-NEW-03 | Rich editor + XSS sanitised | 2 | 2.4, 2.5 |
| UR-NEW-04 | Audience targeting; non-targets never see | 2 | 2.6 |

### Documents (DOC) — Phase 2

| Ref | Summary | Phase | Gate |
|-----|---------|:-----:|------|
| UR-DOC-01 | Categories Policies/Templates/Guides/Forms + nesting | 2 | 2.13 |
| UR-DOC-02 | Full-text search inside PDF/Office | 2 | 2.14, 2.15 |
| UR-DOC-03 | Current version labelled, default download | 2 | 2.16 |
| UR-DOC-04 | Version history retained | 2 | 2.17, 2.18 |
| UR-DOC-05 | Restore creates new version | 2 | 2.17 |
| UR-DOC-07 | Visibility all/dept/team/users; category inherit | 2 | 2.20, 2.21 |
| UR-DOC-10 | Trash 30 days + restore with history | 2 | 2.24 |
| UR-DOC-12 | Duplicate checksum warning; named owner; exception report | 2 | 2.26, 2.27 |

### Policy (POL) — Phase 2

| Ref | Summary | Phase | Gate |
|-----|---------|:-----:|------|
| UR-POL-01 | Policy hub list | 2 | 2.29 |
| UR-POL-02 | Mandatory ack against specific version | 2 | 2.30, 2.31 |
| UR-POL-03 | Compliance matrix + CSV/XLSX export | 2 | 2.32 |
| UR-POL-06 | Status chips Current / Due / Overdue | 2 | 2.35 |

### Dashboard (DSH) — Phase 3

| Ref | Summary | Phase | Gate |
|-----|---------|:-----:|------|
| UR-DSH-01 | Personalised widgets filtered by access | 3 | 3.1, 3.2 |
| UR-DSH-03 | Shell < 1.5s; failed widget isolated | 3 | 3.4, 3.5 |

### Calendar (CAL) — Phase 3

| Ref | Summary | Phase | Gate |
|-----|---------|:-----:|------|
| UR-CAL-01 | Month / week / list views | 3 | 3.7 |
| UR-CAL-02 | Colour-coded categories + filters | 3 | 3.8 |
| UR-CAL-03 | .ics download + personal ICS feed | 3 | 3.9, 3.10 |
| UR-CAL-05 | Audience targeting | 3 | 3.12 |

### Search (SCH) — Phase 3

| Ref | Summary | Phase | Gate |
|-----|---------|:-----:|------|
| UR-SCH-01 | Omnibox across 6 types; Cmd/Ctrl+K | 3 | 3.14, 3.22 |
| UR-SCH-02 | p95 < 2s; typeahead < 500ms | 3 | 3.15, 3.16 |
| UR-SCH-03 | Permission filter at query time | 3 | 3.17 |
| UR-SCH-04 | Facets type / dept / date | 3 | 3.18 |

### Projects (PRJ) — Phase 4

| Ref | Summary | Phase | Gate |
|-----|---------|:-----:|------|
| UR-PRJ-01 | Active projects list + RAG + synced_at | 4 | 4.9 |
| UR-PRJ-02 | Detail: milestones, metrics, deep link | 4 | 4.10 |
| UR-PRJ-03 | Role/dept visibility; 403 otherwise | 4 | 4.11 |
| UR-PRJ-04 | Staleness flag > 60 min | 4 | 4.12 |

### Integrations (INT) — Phase 4

| Ref | Summary | Phase | Gate |
|-----|---------|:-----:|------|
| UR-INT-01 | Dashboard SSO + site unaffected | 4 | 4.1–4.3 |
| UR-INT-02 | Drive accessible (mirror or migrate) | 4 | 4.5, 4.6 |
| UR-INT-03 | Plane sync ≤ 15 min | 4 | 4.7 |
| UR-INT-05 | Usable when any integration fails | 4 | 4.14–4.17 |
| UR-INT-06 | Integration health + Sync now | 4 | 4.18, 4.19 |

### Administration (ADM) — Phases 0, 1, 5

| Ref | Summary | Phase | Gate |
|-----|---------|:-----:|------|
| UR-ADM-01 | User CRUD, roles, MFA reset | 1 | 1.17 |
| UR-ADM-02 | Permission matrix; immediate effect | 1 | 1.20, 1.21 |
| UR-ADM-03 | Admin > Manager > Staff > Guest | 0–1 | 0.9 |
| UR-ADM-04 | Audit log actor/action/entity/before-after/IP/time | 0–5 | 0.15, 1.22, 5.37 |
| UR-ADM-05 | Branding, timeout, categories, quick links admin-editable | 0, 5 | 5.38 |
| UR-ADM-07 | GDPR subject-access export | 5 | 5.35 |

### Non-functional (NFR) — Phase 5 (standards from Phase 0)

| Ref | Summary | Phase | Gate |
|-----|---------|:-----:|------|
| UR-NFR-01 | p95 page < 3s at 100 concurrent | 5 | 5.11 |
| UR-NFR-02 | Search p95 < 2s @ 5k docs | 5 | 5.12 |
| UR-NFR-03 | 99.9% uptime; monitoring | 5 | 5.30–5.32 |
| UR-NFR-04 | 500 named / 100 concurrent | 5 | 5.13 |
| UR-NFR-05 | WCAG 2.1 AA | 5 | 5.15–5.18 |
| UR-NFR-06 | Responsive + browser matrix | 5 | 5.19, 5.20 |
| UR-NFR-07 | TLS 1.3; AES-256 at rest; secrets store | 5 | 5.9, 0.16 |
| UR-NFR-08 | RPO 24h / RTO 4h — restore drill | 5 | 5.27, 5.28 |
| UR-NFR-09 | OWASP; CSRF/XSS/SQLi/IDOR tested | 5 | 5.1–5.8 |
| UR-NFR-10 | GDPR privacy + retention | 5 | 5.34, 5.36 |
| UR-NFR-11 | Maintainable codebase + 30-min README | 0–5 | 0.1–0.5 |
| UR-NFR-12 | Docs + training | 5 | 5.48–5.51 |

---

## Should (stretch within phase if capacity)

| Ref | Phase | Note |
|-----|:-----:|------|
| UR-DIR-07, UR-DIR-08 | 1 | Org chart; mailto/tel |
| UR-NEW-05…08, UR-NEW-10 | 2 | Schedule, reads, alert banner, digest, approval |
| UR-DOC-06, 08, 09, 11 | 2 | Preview, favourites, multi-upload, download log |
| UR-POL-04, UR-POL-05 | 2 | Review reminders; ack nudges |
| UR-DSH-02, UR-DSH-04 | 3 | Widget layout; quick links |
| UR-CAL-04 | 3 | Recurrence |
| UR-SCH-05 | 3 | Typos + synonyms |
| UR-PRJ-05 | 4 | Manual projects |
| UR-INT-04 | 4 | Governex (or CSV) |
| UR-ADM-06 | 0 | Module toggle |

## Could (Phase 6 / buffer)

| Ref | Phase |
|-----|:-----:|
| UR-DIR-10 | 3 widget |
| UR-NEW-09 | 2 comments |
| UR-CAL-06 | 3 RSVP |
| UR-SCH-06 | 3 zero-result report |

## PRD → URD quick map

| PRD | URD areas |
|-----|-----------|
| FR-1 | DSH, AUT-01/02, INT-01 |
| FR-2 | NEW |
| FR-3 | DOC |
| FR-4 | DIR |
| FR-5 | PRJ, INT-03/04 |
| FR-6 | CAL |
| FR-7 | ADM, AUT-03/06 |
| Policy hub | POL |
| Search | SCH |
