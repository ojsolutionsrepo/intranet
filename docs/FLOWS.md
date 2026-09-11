# Runtime flows

Sequence diagrams for the paths that implement authorisation, storage, and integrations. Companion to [TECHNICAL.md](TECHNICAL.md).

URLs assume the XAMPP Alias (`APP_URL=http://localhost/intranet`). Route **names** are what the code uses.

---

## 1. First-run install

```mermaid
sequenceDiagram
    actor User
    participant Apache
    participant MW as RedirectIfNotInstalled
    participant Install as InstallController
    participant FS as storage/app/installed

    User->>Apache: GET /intranet/
    Apache->>MW: Laravel kernel
    alt lock file missing
        MW-->>User: Redirect install.requirements
        User->>Install: Requirements → Database → Admin
        Install->>Install: migrate, seed, create admin
        Install->>FS: write lock file
        Install-->>User: install.complete → login
    else lock file present
        MW->>MW: continue
    end
```

---

## 2. Local login and session

```mermaid
sequenceDiagram
    actor Staff
    participant Fortify
    participant User as users
    participant Audit as AuditLogger
    participant Idle as EnsureSessionIsActive

    Staff->>Fortify: POST /login (throttle login 5/min)
    Fortify->>User: credentials + is_active
    alt 2FA enabled
        Fortify-->>Staff: two-factor challenge (throttle 5/min)
        Staff->>Fortify: TOTP / recovery code
    end
    Fortify->>Audit: action login
    Fortify-->>Staff: Redirect dashboard
    Staff->>Idle: subsequent requests
    Idle->>Idle: is_active and last_activity vs session_idle_timeout
```

SSO (`GET /sso/callback/{provider}`) uses `SsoAdapter`, matches email to a pre-provisioned active user, audits `sso.login`, and refuses unknown accounts. Provider `local` is rejected on this route.

---

## 3. Authorisation (every module page)

```mermaid
flowchart TD
    A[Request] --> B{auth + session.active}
    B -->|fail| Z[Login / 403 inactive]
    B --> C{can:permission Gate}
    C -->|fail| Y[403]
    C --> D{Eloquent policy?}
    D -->|no| E[Controller / Livewire]
    D --> F{permission + isVisibleTo}
    F -->|deny| Y
    F --> E
    E --> G[AudienceResolver.allows]
    G -->|empty audience| H[Company-wide allow]
    G -->|OR users roles depts teams| I[Allow or 403]
```

Search repeats `isVisibleTo` (or equivalent) **inside** `SearchService` so hits never leak in JSON typeahead.

---

## 4. Document upload and download

```mermaid
sequenceDiagram
    actor Staff
    participant Ctrl as DocumentController
    participant Svc as DocumentService
    participant Scan as VirusScanner
    participant Store as DocumentStorageAdapter
    participant Local as LocalStorageDriver
    participant Drive as DriveBroker
    participant Audit as AuditLogger

    Staff->>Ctrl: POST documents.store (can:documents.upload)
    Ctrl->>Svc: upload(user, data, file)
    Svc->>Svc: extension allow-list
    Svc->>Scan: scan(path)
    alt infected
        Scan-->>Staff: RuntimeException
    end
    Svc->>Svc: SHA-256 duplicate warning
    Svc->>Store: put(path, bytes)
    Store->>Local: write always
    opt Drive connected
        Store->>Drive: upload / update mirror
    end
    Svc->>Svc: Document + DocumentVersion in a transaction
    Svc->>Audit: document.uploaded

    Staff->>Ctrl: GET documents.download (throttle:downloads)
    Ctrl->>Svc: download(document, version, user)
    alt not isVisibleTo
        Svc->>Audit: document.download_denied
        Svc-->>Staff: 403
    else allowed
        Svc->>Audit: document.downloaded
        Svc->>Store: get(storage_ref)
        Store->>Local: read cache
        Svc-->>Staff: file bytes
    end
```

Listings and ACL never call Drive. Local disk is the serve path. `DriveBroker::resolve()` exists to fill cache from a Drive revision when needed.

---

## 5. Staff information (self vs admin lock)

```mermaid
sequenceDiagram
    actor Staff
    actor Admin
    participant Self as StaffInformationController
    participant Svc as StaffInformationService
    participant Model as StaffInformation
    participant Disk as public disk

    Staff->>Self: POST directory.staff-information.update
    Self->>Svc: save(subject=staff, actor=staff, data, photo, cv)
    Svc->>Model: actorCanEdit(staff)
    alt staff_can_edit false and actor is not manager/admin
        Svc-->>Staff: 403
    else allowed
        Svc->>Model: fill + encrypted account_number / sort_code
        opt files
            Svc->>Disk: photo + CV under staff-information/{id}/
        end
        Svc->>Svc: AuditLogger staff_information.updated
    end

    Admin->>Self: POST admin.users.staff-information.editing staff_can_edit=0
    Self->>Svc: setStaffCanEdit(false)
    Note over Staff: subsequent self-updates 403; Admin can still save
```

Bank fields are ciphertext in the database (`encrypted` casts). Feature test: `tests/Feature/StaffInformationTest.php`.

---

## 6. Unified search

```mermaid
sequenceDiagram
    actor Staff
    participant HTTP as SearchController
    participant Svc as SearchService
    participant DB as MySQL / SQLite
    participant Az as Analytics + SearchZeroResult

    Staff->>HTTP: GET search.index or search.suggest (throttle:search)
    HTTP->>Svc: search(user, query, filters)
    Svc->>DB: LIKE over posts, documents, users, departments, events, projects
    loop each hit type
        Svc->>Svc: permission + isVisibleTo / published / not trashed
    end
    Svc->>Az: search.performed
    alt no hits
        Svc->>Az: insert search_zero_results
    end
    Svc-->>Staff: hits + facets + took_ms
```

Meilisearch is a future driver; `aclTokens()` is the planned filter shape. Today this path is in-app.

---

## 7. Project sync (Plane + Governex)

```mermaid
sequenceDiagram
    participant Cron as schedule:every 15 min
    participant Cmd as intranet:sync-integrations
    participant Svc as ProjectSyncService
    participant CB as CircuitBreaker
    participant Plane as PlaneAdapter
    participant Gov as GovernexAdapter
    participant DB as projects

    Cron->>Cmd: handle
    Cmd->>Svc: syncAll
    Svc->>CB: call Plane fetch
    CB->>Plane: fetchProjects / milestones
    alt 5 consecutive failures
        CB->>CB: circuit open 60s
        Note over Svc: widgets show stale if synced_at older than PROJECT_STALENESS_MINUTES
    else success
        Svc->>DB: upsert on source + external_ref
        CB->>CB: recordSync
    end
    Svc->>CB: call Governex (API or CSV driver, same interface)
    CB->>Gov: fetch
    Svc->>DB: upsert
```

Admin can also `POST admin.integrations.sync`. Manual projects skip this path (`source` not plane/governex).

---

## 8. Request middleware stack (web)

```mermaid
flowchart LR
    Q[HTTP] --> R[RedirectIfNotInstalled]
    R --> S[SecureHeaders]
    S --> T[Encrypt cookies / session / CSRF]
    T --> U[auth]
    U --> V[session.active]
    V --> W[can:permission and/or admin]
    W --> X[Controller]
```

`SecureHeaders` always runs on the web group, including login. CSP allows Vite (`localhost:5173`) only when `APP_ENV=local`.
