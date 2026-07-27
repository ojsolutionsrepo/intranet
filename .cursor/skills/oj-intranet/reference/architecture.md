# Architecture (OJ-INTRA-ARC-001)

Condensed from `04_System_Architecture.md`. Read the source for full prose; follow these patterns in code.

## Layers

```text
Clients:     Browser · Mobile web · Outlook · Dashboard
Edge:        CloudFront + WAF · ALB + nginx
Application: Laravel 11 modular monolith (feature modules + Core + Horizon)
Adapters:    Storage · Calendar · Identity · Projects  (swappable)
Data:        MySQL 8 · Redis 7 · Meilisearch · S3 cache
Third-party: Google Drive/Calendar · OIDC · Plane.so · Governex
```

WAF rate limits: login 5/min, search 30/min, download 100/hr. Min two ECS tasks across two AZs.

## Module registration

```php
$registry->register('directory')
    ->permissions([...])
    ->menu(fn () => [...])
    ->migrations(__DIR__.'/Database/Migrations')
    ->views(__DIR__.'/Resources/views', 'directory');
```

Hooks (customisability without forks):

```php
Hook::action('document.uploaded', $document);
$columns = Hook::filter('directory.profile.fields', $defaultFields);
```

Client-specific overlays live in `app/Modules/Custom/` and hook in.

## Adapter interfaces

Modules depend on interfaces only. Drivers are config-swappable.

### DocumentStorageAdapter

`put`, `get`, `signedUrl($ref, $ttlSeconds = 300)`, `newVersion`, `listVersions`, `delete`, `health`.

Drivers: `GoogleDriveStorageDriver`, `S3StorageDriver`, `LocalStorageDriver`.

### CalendarAdapter

`createEvent`, `updateEvent`, `deleteEvent`, `listEvents`, `watchChannel`, `renewChannel`.

Drivers: `GoogleCalendarDriver`, `NativeCalendarDriver`, `MicrosoftGraphDriver` (future).

### IdentityProvider

`redirectUrl`, `handleCallback`, `fetchUser`, `revokeSession`.

Drivers: `GoogleOidcDriver`, `LocalCredentialsDriver`, `KeycloakOidcDriver` (future).

### ProjectSourceAdapter

`fetchProjects`, `fetchMilestones`, `deepLink`.

Drivers: `PlaneDriver`, `GovernexDriver`, `ManualDriver`.

### Resilience decorator (wrap every driver)

- Timeout 10s
- Retry 3× exponential backoff
- Circuit breaker: open after 5 consecutive failures; half-open after 60s
- Health reporting to admin integration page

Modules never implement these concerns themselves.

## Document download broker

Steady-state listings make **zero** Drive API calls (MySQL + Meilisearch only).

```text
Staff clicks download
  → Session check (OIDC or local)
  → DocumentPolicy (dept / team / named user)
       denied → 403 + attempt logged
  → S3 cache lookup (keyed by checksum)
       hit  → serve via signed URL
       miss → Drive fetch (service account, pinned rev)
            → cache response in S3
  → Signed URL, 5 min TTL
  → Audit log (user, version, timestamp)
```

- Shared Drive only; service account; credentials in Secrets Manager
- MySQL `document_versions` is authoritative (version, uploader, SHA-256, changelog, Drive `revisionId`)
- Pin every uploaded revision (`keepForever: true`)
- Restore = new version pointing at old revision (never destroy)
- Drive down → cached docs still download; uncached show "temporarily unavailable"

## Calendar topology

Google Calendar is source of truth; MySQL mirrors for dashboard/search.

| Calendar | Audience | Write |
|----------|----------|-------|
| OJ — All Company | Everyone | Comms / HR |
| OJ — Training | Everyone | HR |
| OJ — Deadlines | Everyone | PMO |
| OJ — {Department} | That dept | Dept lead |

Watch channels expire ~7 days — renew daily; alert if within 24h of expiry. Nightly full reconciliation.

## Identity

- Google Workspace OIDC (Workspace MFA) → match email to pre-provisioned local `users`
- Unrecognised Google account refused
- Roles via `spatie/laravel-permission` — never Google Groups
- `LocalCredentialsDriver` always enabled (break-glass); Admin requires TOTP
- Deprovision: reconcile Workspace Directory every 15 min; manual deactivate kills sessions ≤ 60s

## Search

Meilisearch via Scout. Index: documents (extracted text), posts, users, departments, projects, events.

**Permission filter at query time** — each record carries `acl` array; query filter = user's entitlements. Never index-then-hide in the view.

Indexing queued: upload → extract (`index` queue) → index. Full reindex rebuildable from MySQL.

## Projects sync

Poll Plane / Governex every 15 min; upsert on `external_ref` + `source`. Show `synced_at`; flag > 60 min stale. Circuit open → plain staleness message. Governex without API → CSV driver, same interface.

## Core schema (Phase 0+)

```
users ─ user_profiles
      ─ model_has_roles ─ roles ─ permissions
      ─ department_user
departments (parent_id) · teams · team_user
documents · document_versions · document_categories · document_acknowledgements
posts · post_reads
projects · project_milestones
events · event_attendees
audit_logs · settings · modules
```

`audience` JSON on posts/events: `{"departments":[1,4],"teams":[],"roles":["staff"]}` — resolve only via shared `AudienceResolver`.

## Queues (Horizon)

| Queue | Purpose |
|-------|---------|
| `default` | General jobs |
| `sync` | Integration polling |
| `index` | Text extraction + search indexing |
| `mail` | Digests, nudges, resets |

## Security baseline

TLS 1.3 · HSTS · CSP · X-Frame-Options · Referrer-Policy · RDS/S3 KMS · Secrets Manager · MIME sniff uploads · ClamAV on queue · storage outside webroot · audit on every state change and download.

## Reversibility

Swap Drive → S3, Calendar → Graph/Native, Identity → Keycloak by adding a driver + config flip. Feature modules untouched.
