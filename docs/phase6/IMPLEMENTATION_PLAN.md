# Phase 6 Implementation Plan

| Field | Value |
|-------|-------|
| Document ID | OJ-INTRA-IMP-006 |
| Status | Draft — planning only |
| Relates to | OJ-INTRA-IMP-001 §9 · OJ-INTRA-URD-001 Could · post-MVP |
| Audience | Sponsor, PM, Tech Lead, module owners |
| Skill refs | `.cursor/skills/oj-intranet/reference/phases.md` · `requirements-map.md` · `gates.md` |

---

## 1. Purpose

Deliver post-MVP capability after Gate 5 sign-off: close parked Could backlog, then ship a reusable workflow engine, employee journeys, a lightweight LMS, an analytics dashboard over existing event capture, and extended integrations (Zenzap, HR/payroll, PWA).

Phase 6 is **out of MVP scope**. It does not block go-live.

## 2. Prerequisite

| Condition | Rule |
|-----------|------|
| Gate 5 | Must be **Pass** or **Pass with dated condition**. Zero open Must fails. |
| Week 10 buffer | May only seed **6.1 workflow spike** or deepen hypercare runbooks — not LMS, Experience full build, or PWA. |
| Non-negotiables | Same as MVP: adapters only, policies as authz authority, degrade-never-fail, modular monolith, audit, DS-001 tokens. |

## 3. Timeline (indicative Weeks 11–25)

| Weeks | Sub-phase | Focus |
|------:|-----------|--------|
| 11–12 | 6.0 + 6.1 spike | Could backlog + workflow engine spike |
| 13–14 | 6.1 | Workflow complete + News approval consumer |
| 14–17 | 6.2 | Employee experience / journeys |
| 17–20 | 6.3 | LMS lite |
| 18–21 | 6.4 | Analytics dashboard (overlaps LMS) |
| 21–24 | 6.5 | Zenzap, HR/payroll, PWA |
| 25 | Gate 6 | Sign-off / buffer |

## 4. Architecture principles

- New capability lives under `app/Modules/{Name}/` with `module.json`, ServiceProvider, Policies, Pest tests.
- Externals only via `app/Shared/Contracts/*` + drivers; circuit breaker + staleness UI.
- Prefer hooks (`Hook::action` / `Hook::filter`) so Workflow does not hard-code News/Documents.
- Reuse `AudienceResolver`, `AuditLogger`, `Analytics::track()`, widget registry.
- Feature test for every new `UR-*` before marking done.

```text
Clients → Laravel modular monolith
  Modules: Workflow · Experience · Learning · (Admin analytics views)
  Shared: Analytics · AudienceResolver · AuditLogger · adapters
  Data: MySQL · Redis · Meilisearch (unchanged pattern)
```

---

## 5. Sub-phase 6.0 — Could backlog (Weeks 11–12)

**Objective:** Close parked Could URs that already have footholds in the MVP codebase.

| # | Task | UR | Output |
|---|------|-----|--------|
| 6.0.1 | Admin zero-result report UI + CSV export; date/user filters | UR-SCH-06 | Admin Search insights page |
| 6.0.2 | News comments (threaded); ACL = post audience; XSS sanitised | UR-NEW-09 | Comments on post show |
| 6.0.3 | Event RSVP (going / maybe / declined) + attendee list | UR-CAL-06 | RSVP Livewire on event |
| 6.0.4 | Directory / new-joiners widget polish (DIR widget Could) | UR-DIR-10 | Dashboard widget DoD |

**Leverage**

| UR | Existing foothold |
|----|-------------------|
| UR-SCH-06 | `SearchZeroResult` model + logging in `SearchService` |
| UR-NEW-09 | News module + AudienceResolver + HTML sanitisation patterns |
| UR-CAL-06 | `EventAttendee` model path in Calendar module |
| UR-DIR-10 | `NewJoinersWidget` + WidgetRegistry |

**DoD:** Each Could UR has a Pest feature test; Admin can export zero-results; RSVP and comments respect audience ACL (IDOR covered).

---

## 6. Sub-phase 6.1 — Workflow engine (Weeks 11–14)

**Objective:** Generic workflow module usable by News, Documents, Policies, and later Experience — not a News-only state machine.

**Module:** `app/Modules/Workflow/`

```text
Definition (JSON) → WorkflowEngine → instances + tasks → Hook actions
                                              ↓
                         News approval · Doc review · Policy nudges
```

| # | Task | UR | Output |
|---|------|-----|--------|
| 6.1.1 | Schema: `workflow_definitions`, `workflow_instances`, `workflow_tasks` | UR-WFL-01 | Migrations + models |
| 6.1.2 | Engine: start, transition, assign, cancel; configurable states | UR-WFL-01 | `WorkflowEngine` service |
| 6.1.3 | Policies + audit on every transition | UR-WFL-02 | Policy + AuditLogger |
| 6.1.4 | Admin UI: list definitions, inspect instances | UR-WFL-03 | Livewire admin |
| 6.1.5 | First consumer: News Draft → In Review → Approved/Rejected → Published (hooks) | UR-WFL-04 · UR-NEW-10 | News wired without SDK coupling |
| 6.1.6 | Pest: happy path + unauthorized transition 403 | UR-WFL-02 | `Phase6WorkflowTest` |

**Default state template (overridable per definition):** Draft → In Review → Approved | Rejected → Published.

**DoD:** News all-company Staff posts can require approval via workflow; unauthorized transition fails closed; every transition audited.

---

## 7. Sub-phase 6.2 — Employee experience (Weeks 14–17)

**Objective:** Structured joiners / role journeys with manager sign-off, powered by Workflow.

**Module:** `app/Modules/Experience/`

| # | Task | UR | Output |
|---|------|-----|--------|
| 6.2.1 | Journey definitions (checklist packs by role/dept) | UR-EXP-01 | Models + admin CRUD |
| 6.2.2 | Enrolment on join / manual assign; Workflow-backed steps | UR-EXP-02 | Instance per user |
| 6.2.3 | Manager sign-off tasks | UR-EXP-03 | Task UI + notify |
| 6.2.4 | Dashboard widgets: My tasks / Outstanding journeys | UR-EXP-04 | WidgetRegistry entries |
| 6.2.5 | Notifications (mail + in-app); Zenzap remains quick-link until 6.5 | UR-EXP-05 | Notification classes |

**DoD:** New joiner receives a journey; manager closes a step; Staff sees outstanding tasks on dashboard; ACL prevents viewing others’ private journey notes.

---

## 8. Sub-phase 6.3 — LMS lite (Weeks 17–20)

**Objective:** Lightweight learning without a third-party LMS dependency in v1.

**Module:** `app/Modules/Learning/`

**Adapter:** `App\Shared\Contracts\LearningProvider` — `LocalLearningDriver` first; third-party driver later.

| # | Task | UR | Output |
|---|------|-----|--------|
| 6.3.1 | Courses → modules → content (doc link / URL / video URL) | UR-LMS-01 | Schema + UI |
| 6.3.2 | Enrolment + completion tracking + progress % | UR-LMS-02 | Progress service |
| 6.3.3 | Mandatory courses linked to Policy hub / compliance | UR-LMS-03 | Bridge to Policies |
| 6.3.4 | Admin completion matrix + CSV export (POL-03 pattern) | UR-LMS-04 | Export action |
| 6.3.5 | Profile progress summary | UR-LMS-05 | Directory profile section |

**DoD:** Staff completes a course; Admin exports completion matrix; mandatory course appears in compliance context; LearningProvider is swappable.

---

## 9. Sub-phase 6.4 — Analytics dashboard (Weeks 18–21)

**Objective:** Make Phase 3+ `Analytics::track()` and `search_zero_results` actionable for PRD success metrics.

**Surface:** Admin Livewire under Admin module (no separate BI stack in v1).

| # | Task | UR | Output |
|---|------|-----|--------|
| 6.4.1 | Aggregate views: search success, downloads, news reach, zero-results, login/SSO | UR-ANL-01 | Admin Analytics page |
| 6.4.2 | Date range + facet filters; CSV export | UR-ANL-02 | Filters + export |
| 6.4.3 | Expand `track()` call sites where gaps remain | UR-ANL-03 | Instrumentation pass |
| 6.4.4 | Retention aligned with `config/gdpr.php` | UR-ANL-04 · UR-NFR-10 | Prune job / policy |

**DoD:** Sponsor can view weekly search + engagement summary; exports work; retention prune documented and dry-run tested.

---

## 10. Sub-phase 6.5 — Extended integrations + PWA (Weeks 21–24)

**Objective:** Same Phase 4 adapter pattern for new externals; progressive web app shell.

| # | Task | UR | Approach |
|---|------|-----|----------|
| 6.5.1 | Zenzap notify adapter (webhook / deep-link) | UR-INT-07 | Interface + driver; modules never import Zenzap SDK |
| 6.5.2 | HR / payroll read-only sync into Directory fields | UR-INT-08 | CSV/API adapter; circuit breaker + staleness |
| 6.5.3 | PWA manifest + service worker (shell offline) | UR-PWA-01 | `manifest.webmanifest` + SW; auth API unchanged |
| 6.5.4 | Optional push opt-in | UR-PWA-02 | Permission gated; degrade if unsupported |
| 6.5.5 | Integration health entries for new adapters | UR-INT-06 (extend) | Admin health page |

**DoD:** Kill Zenzap/HR adapters — core stays up with clear staleness; PWA installs on supported browsers; secrets not in repo.

---

## 11. Cross-cutting

| Concern | Rule |
|---------|------|
| Authz | Policies on every new route; IDOR suite extended |
| Audit | Create/update/transition/download/export |
| Design | DS-001 tokens only |
| Tests | Pest feature test per `UR-*` before Done |
| Progress | Update `progress-checks.json` as modules land; `php artisan intranet:progress --write` |
| Commits | Conventional Commits; PR links requirement ID |

## 12. De-scope ladder (if Weeks slip)

Cut in this order — **never** cut Workflow core + UR-SCH-06 once started:

1. PWA push (UR-PWA-02) → full PWA (UR-PWA-01)
2. HR / payroll sync (UR-INT-08)
3. LMS video content / third-party LearningProvider
4. Experience multi-journey packs (keep single joiner journey)
5. Keep: Workflow engine + News consumer + SCH-06 zero-result report

## 13. Gate 6 — Post-MVP verification

**Approvers:** Sponsor (Jon) + PM (Feranmi) + Tech Lead / IT (Victor)

| Track | Critical proof |
|-------|----------------|
| 6A Could backlog | SCH-06, NEW-09, CAL-06, DIR-10 Pass with Pest |
| 6B Workflow | Definition → instance → News approval; unauthorized transition 403; audits |
| 6C Experience | Joiner journey + manager sign-off + dashboard tasks |
| 6D LMS | Course complete + compliance matrix export |
| 6E Analytics | PRD metric dashboard + GDPR retention |
| 6F Integrations / PWA | Adapter degrade tests; PWA install smoke; no secrets in repo |

**Statuses:** Pass · Pass with condition (dated) · Fail · N/A (why).

A Gate 6 Must item (as defined for Phase 6 delivery — default all listed URs above unless de-scoped in writing) cannot Fail at sign-off.

## 14. Requirement ID catalogue (Phase 6)

### Parked Could (from URD)

| Ref | Summary | Sub-phase |
|-----|---------|-----------|
| UR-DIR-10 | Directory / new-joiners widget | 6.0 |
| UR-NEW-09 | News comments | 6.0 |
| UR-CAL-06 | Calendar RSVP | 6.0 |
| UR-SCH-06 | Zero-result report | 6.0 |
| UR-NEW-10 | News approval (Should — consumed by Workflow) | 6.1 |

### New draft IDs (IMP-006)

| Ref | Summary | Sub-phase |
|-----|---------|-----------|
| UR-WFL-01 | Workflow definitions + engine | 6.1 |
| UR-WFL-02 | Authorised transitions + audit | 6.1 |
| UR-WFL-03 | Admin workflow inspection | 6.1 |
| UR-WFL-04 | News wired as first consumer | 6.1 |
| UR-EXP-01 | Journey definitions | 6.2 |
| UR-EXP-02 | Enrolment + workflow steps | 6.2 |
| UR-EXP-03 | Manager sign-off | 6.2 |
| UR-EXP-04 | Dashboard task widgets | 6.2 |
| UR-EXP-05 | Journey notifications | 6.2 |
| UR-LMS-01 | Courses / modules / content | 6.3 |
| UR-LMS-02 | Enrolment + progress | 6.3 |
| UR-LMS-03 | Mandatory / policy link | 6.3 |
| UR-LMS-04 | Completion matrix export | 6.3 |
| UR-LMS-05 | Profile progress | 6.3 |
| UR-ANL-01 | Admin analytics aggregates | 6.4 |
| UR-ANL-02 | Filters + CSV export | 6.4 |
| UR-ANL-03 | Instrumentation completeness | 6.4 |
| UR-ANL-04 | Event retention / GDPR | 6.4 |
| UR-INT-07 | Zenzap adapter | 6.5 |
| UR-INT-08 | HR/payroll directory sync | 6.5 |
| UR-PWA-01 | Installable PWA shell | 6.5 |
| UR-PWA-02 | Push opt-in | 6.5 |

## 15. Open blockers (flag before building each stream)

| # | Item | Blocks |
|---|------|--------|
| 1 | Gate 5 signed | Entire Phase 6 (except Week 10 spike) |
| 2 | Zenzap API/webhook availability | UR-INT-07 |
| 3 | HR/payroll export format + data region | UR-INT-08 |
| 4 | Mandatory course catalogue owners | UR-LMS-03 |
| 5 | Journey pack content from HR | UR-EXP-01 |

## 16. Related artefacts

| Artefact | Path |
|----------|------|
| Skill phases | `.cursor/skills/oj-intranet/reference/phases.md` |
| Requirements map | `.cursor/skills/oj-intranet/reference/requirements-map.md` |
| Gates | `.cursor/skills/oj-intranet/reference/gates.md` |
| Progress checks | `.cursor/skills/oj-intranet/progress-checks.json` |
| Progress report | `docs/IMPLEMENTATION_PROGRESS.md` |
| Analytics foothold | `app/Shared/Services/Analytics.php` |
| Zero-result foothold | `app/Modules/Search/Models/SearchZeroResult.php` |

---

_Document control: update status to Active when Gate 5 is signed and 6.0/6.1 work begins. Revise de-scope decisions in writing with PM._
