# Phase Gate Verification (OJ-INTRA-CHK-001)

Full checklist: `03_Phase_Gate_Verification_Checklist.md`. Run gates with approvers present (not by email). Verify on **staging by demonstration**.

**Statuses:** Pass · Pass with condition (record due date) · Fail (blocks if Must) · N/A (record why).

**Defects:** P1 blocks go-live · P2 hypercare · P3 backlog.

A gate cannot sign with any Must-priority Fail outstanding.

---

## Gate 0 — Foundation (end Week 1)

**Approver:** Tech Lead

Critical items:

- README → setup < 30 min on XAMPP Apache; Docker optional
- CI: Pint + PHPStan (6 / Core 8) + Pest green; deliberate PSR-12 fails build
- Module register/disable; hooks fire
- Roles seeded; local login + reset; Admin TOTP; idle timeout; Staff 403 `/admin`
- Admin shell with DS-001 brand tokens
- Audit: login + settings change
- Staging HTTPS; daily DB snapshot configured

**Outcome required before Phase 1.**

---

## Gate 1 — Directory & RBAC (end Week 3)

**Approvers:** PM (Feranmi) + HR (Debby)

Critical items:

- All departments/teams; full staff import; **zero users without department**
- Import preview rejects bad rows without commit
- Name search < 2s + typo tolerance; filters combine
- Profile 10 fields; self-edit limits; 403 on others
- Deactivate kills live session ≤ 60s; content/audit intact
- Permission matrix saves; effect immediate
- Feature tests UR-DIR-01…09

**Persona — Jasmine:** find dept members, skill colleague, extension < 90s unaided.

---

## Gate 2 — News & Documents (end Week 5)

**Approvers:** PM + Content Owners + HR

### 2A News (critical)

- Feed + infinite scroll + pin
- XSS: pasted `<script>` does not execute
- Audience: Dept A post invisible to Dept B (feed, search, URL)
- Approval workflow for Staff all-company posts (Should)

### 2B Documents (critical)

- Nested categories + breadcrumbs
- Search phrase **only inside PDF/DOCX/XLSX body**
- Version cycle: upload v1 → v2 → download v1 → restore v1 → three versions, no loss
- Dept ACL on list, search, direct URL/download
- Spoofed extension rejected; trash 30-day restore
- Duplicate checksum warning; named owners

### 2C Policy (critical)

- Mandatory ack recorded against **specific version**; new version resets
- Compliance matrix export; status chips correct

**Persona — Debby:** publish mandatory policy, target two depts, acknowledgement report — no developer help.

---

## Gate 3 — Dashboard, Calendar, Search (end Week 6)

**Approvers:** PM + Sponsor

Critical items:

- Two users see different dashboards; shell < 1.5s; failed widget isolated
- Calendar month/week/list; .ics + personal feed (real Outlook + Google)
- Audience-targeted event invisible to non-members
- Search across 6 types; p95 < 2s @ ≥5k docs; typeahead < 500ms
- **Two-user differential search** (same term, different ACL results)
- Cmd/Ctrl+K keyboard nav; analytics events capturing

**Persona — Seyitan:** find unknown project template via search alone < 60s.

---

## Gate 4 — Integrations (end Week 7)

**Approvers:** Tech Lead + IT Admin (Victor)

Critical items:

- SSO dashboard ↔ intranet both directions; public site unaffected
- Local login when SSO down
- Drive visible (broker/mirror/migrate as configured)
- Plane ≤ 15 min; Governex or documented fallback
- Project RBAC 403; staleness > 60 min flagged
- **Degradation:** SSO / Drive / Plane / Governex each killed — core usable + clear staleness
- Integration health page + Sync now; secrets not in repo; circuit breaker recovers

---

## Gate 5 — Hardening & Go-Live (end Weeks 8–9)

**Approvers:** Sponsor (Jon) + PM + IT Admin

| Track | Critical proof |
|-------|----------------|
| 5A Security | Zero critical/high OWASP; every route has policy; IDOR suite; CSRF/XSS; rate limits; headers; ClamAV; audits clean |
| 5B Perf / a11y | k6 100 users p95 < 3s; search < 2s; axe-core zero; keyboard + NVDA five flows; contrast AA; responsive matrix |
| 5C Migration | Taxonomy signed pre-run; dry-run 10% sample; checksum reconciliation; rollback documented |
| 5D Ops | **Restore drill within RTO 4h**; PITR; monitoring + tested on-call alert; deploy runbook executed once |
| 5E GDPR | Privacy notice; subject-access export; retention; audit export |
| 5F UAT | Pack for 5 personas; ≥8 testers; **all P1 closed**; each persona script passed |
| 5G Adoption | Training recorded; guides; champions; hypercare channel; launch comms |
| 5H Benefits | Pre-launch baselines + measurement plan for PRD metrics |

---

## Gate 6 — Post-MVP (Weeks 11–25)

**Approvers:** Sponsor (Jon) + PM (Feranmi) + Tech Lead / IT (Victor)

**Prerequisite:** Gate 5 signed. Full checklist: [`docs/phase6/IMPLEMENTATION_PLAN.md`](../../../../docs/phase6/IMPLEMENTATION_PLAN.md) §13.

| Track | Critical proof |
|-------|----------------|
| 6A Could backlog | SCH-06, NEW-09, CAL-06, DIR-10 Pass with Pest |
| 6B Workflow | Definition → instance → News approval; unauthorized transition 403; audits |
| 6C Experience | Joiner journey + manager sign-off + dashboard tasks |
| 6D LMS | Course complete + compliance matrix export |
| 6E Analytics | PRD metric dashboard + GDPR retention |
| 6F Integrations / PWA | Adapter degrade tests; PWA install smoke; no secrets in repo |

De-scope only per IMP-006 §12 (written PM decision). Do not claim Gate 6 with open in-scope Fail items.

---

## Sign-off roles

| Role | Name | Confirms |
|------|------|----------|
| Sponsor | Jon | Must delivered; benefits realisable |
| PM | Feranmi | Gates passed; P1 closed |
| IT / Tech | Victor | Security, resilience, ops |
| HR | Debby | Directory, policy, compliance |
| PMO | Seyitan | Project dashboards |
| Operations | PFems | Comms and calendar |

---

## Agent checklist before claiming a phase done

1. Open matching Gate section above
2. Confirm every Must gate item has a demo path or automated test
3. Run persona walkthrough if listed
4. Update coverage mentally against `requirements-map.md` Must table
5. Do not mark phase complete with open Must fails
