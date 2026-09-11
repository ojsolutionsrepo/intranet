# HTTP route catalogue

Named routes as registered in code. Prefix URIs with `APP_URL` (e.g. `http://localhost/intranet`).

Fortify also registers login, logout, password reset, 2FA challenge, and password confirm routes (see `vendor/laravel/fortify` + `config/fortify.php`). Registration is **disabled**.

Default middleware for module pages unless noted: `web`, `auth`, `session.active`.

---

## Platform

| Method | URI | Name | Extra middleware / notes |
|--------|-----|------|--------------------------|
| GET | `/up` | *(health)* | Laravel health; no auth |
| GET | `/` | — | Redirect dashboard or login |
| GET | `/dashboard` | `dashboard` | `auth`, `session.active` |
| GET | `/privacy` | `privacy.notice` | authenticated |
| GET | `/sso/callback/{provider}` | `sso.callback` | `web` only; not for `local` |

---

## Installer

`install.guest` (blocked after `storage/app/installed`).

| Method | URI | Name |
|--------|-----|------|
| GET | `/install` | `install.requirements` |
| POST | `/install/requirements` | `install.requirements.store` |
| GET | `/install/database` | `install.database` |
| POST | `/install/database` | `install.database.store` |
| GET | `/install/admin` | `install.admin` |
| POST | `/install/admin` | `install.admin.store` |
| GET | `/install/complete` | `install.complete` |

---

## Search

Permission: `search.view`.

| Method | URI | Name | Extra |
|--------|-----|------|--------|
| GET | `/search` | `search.index` | |
| GET | `/search/suggest` | `search.suggest` | JSON; `throttle:search` |

---

## Directory

Permission on most routes: `can:directory.view`. Import: `directory.import`.

| Method | URI | Name |
|--------|-----|------|
| GET | `/directory` | `directory.index` |
| GET | `/directory/org-chart` | `directory.org-chart` |
| GET | `/directory/departments/{department:slug}` | `directory.department` |
| GET | `/directory/people/{user}` | `directory.show` |
| GET | `/directory/profile/edit` | `directory.profile.edit` |
| GET | `/directory/staff-information` | `directory.staff-information.edit` |
| POST | `/directory/staff-information` | `directory.staff-information.update` |
| GET | `/directory/staff-information/cv` | `directory.staff-information.cv` |
| GET | `/directory/import` | `directory.import` |

---

## News

| Method | URI | Name | Permission |
|--------|-----|------|------------|
| GET | `/news` | `news.index` | `news.view` |
| GET | `/news/create` | `news.create` | publish/manage |
| POST | `/news` | `news.store` | publish/manage |
| POST | `/news/inline-image` | `news.inline-image` | publish/manage |
| GET | `/news/{post:slug}` | `news.show` | `news.view` + audience |
| GET | `/news/{post:slug}/attachments/{attachment}` | `news.attachments.download` | `news.view` |

---

## Documents

| Method | URI | Name | Extra |
|--------|-----|------|--------|
| GET | `/documents` | `documents.index` | `documents.view` |
| GET | `/documents/upload` | `documents.upload` | `documents.upload` |
| POST | `/documents/upload` | `documents.store` | `documents.upload` |
| POST | `/documents/categories` | `documents.categories.store` | `documents.upload` |
| GET | `/documents/search` | `documents.search` | `documents.view` |
| GET | `/documents/storage` | `documents.storage` | `signed` |
| GET | `/documents/{document}` | `documents.show` | `documents.view` |
| GET | `/documents/{document}/download` | `documents.download` | `throttle:downloads` |
| GET | `/documents/{document}/preview` | `documents.preview` | `throttle:downloads` |
| GET | `/documents/{document}/versions/{version}/download` | `documents.download-version` | `throttle:downloads` |
| POST | `/documents/{document}/versions/{version}/restore` | `documents.restore-version` | `documents.upload` |
| POST | `/documents/{document}/trash` | `documents.trash` | `documents.manage` |
| POST | `/documents/trashed/{document}/restore` | `documents.restore` | `documents.manage` |

---

## Policies (Documents module)

| Method | URI | Name | Permission |
|--------|-----|------|------------|
| GET | `/policies` | `policies.index` | `policies.view` |
| POST | `/policies/{document}/acknowledge` | `policies.acknowledge` | `policies.view` |
| GET | `/policies/{document}/compliance.csv` | `policies.compliance` | `policies.manage` |

---

## Calendar

| Method | URI | Name | Extra |
|--------|-----|------|--------|
| GET | `/calendar` | `calendar.index` | `calendar.view` |
| GET | `/calendar/create` | `calendar.create` | `calendar.manage` |
| GET | `/calendar/export.ics` | `calendar.ics.download` | authenticated |
| POST | `/calendar/feed-token` | `calendar.ics.token` | authenticated |
| GET | `/calendar/feed/{token}.ics` | `calendar.ics.feed` | **public**; token on `users.ics_token` |

---

## Dashboard extras

| Method | URI | Name |
|--------|-----|------|
| GET | `/dashboard/prefs` | `dashboard.prefs` |

Home shell is `dashboard` (see Platform).

---

## Projects

| Method | URI | Name | Permission |
|--------|-----|------|------------|
| GET | `/projects` | `projects.index` | `projects.view` |
| GET | `/projects/create` | `projects.create` | `projects.manage` |
| GET | `/projects/{project:slug}` | `projects.show` | `projects.view` + audience |

---

## Demo

| Method | URI | Name | Extra |
|--------|-----|------|--------|
| GET | `/demo` | `demo.index` | `demo.view`; 404 if module disabled |

---

## Drive OAuth

Authenticated.

| Method | URI | Name |
|--------|-----|------|
| GET | `/integrations/drive/connect` | `drive.oauth.redirect` |
| GET | `/integrations/drive/callback` | `drive.oauth.callback` |
| POST | `/integrations/drive/disconnect` | `drive.oauth.disconnect` |

---

## Admin

All `/admin/*` routes below also use middleware `admin` (`hasRole('Admin')`) except `admin.index` which uses the same role check via `EnsureUserIsAdmin`.

| Method | URI | Name | Permission |
|--------|-----|------|------------|
| GET | `/admin` | `admin.index` | Admin role |
| GET | `/admin/users` | `admin.users.index` | `admin.users.manage` |
| GET | `/admin/users/create` | `admin.users.create` | `admin.users.manage` |
| GET | `/admin/users/{user}/edit` | `admin.users.edit` | `admin.users.manage` |
| GET | `/admin/users/{user}/staff-information` | `admin.users.staff-information.edit` | `admin.users.manage` |
| POST | `/admin/users/{user}/staff-information` | `admin.users.staff-information.update` | `admin.users.manage` |
| POST | `/admin/users/{user}/staff-information/editing` | `admin.users.staff-information.editing` | `admin.users.manage` |
| GET | `/admin/users/{user}/staff-information/cv` | `admin.users.staff-information.cv` | `admin.users.manage` |
| GET | `/admin/departments` | `admin.departments` | `admin.departments.manage` |
| GET | `/admin/permissions` | `admin.permissions` | `admin.permissions.manage` |
| GET | `/admin/integrations` | `admin.integrations` | `admin.integrations.view` |
| POST | `/admin/integrations/sync` | `admin.integrations.sync` | `admin.integrations.view` |
| GET | `/admin/quick-links` | `admin.quick-links` | `admin.quick_links.manage` |
| GET | `/admin/settings` | `admin.settings` | `admin.settings.manage` |
| POST | `/admin/settings/logo` | `admin.settings.logo` | `admin.settings.manage` |
| DELETE | `/admin/settings/logo` | `admin.settings.logo.remove` | `admin.settings.manage` |
| POST | `/admin/settings/favicon` | `admin.settings.favicon` | `admin.settings.manage` |
| DELETE | `/admin/settings/favicon` | `admin.settings.favicon.remove` | `admin.settings.manage` |
| GET | `/admin/compliance` | `admin.compliance` | `admin.compliance.export` |
| POST | `/admin/compliance/subject-access` | `admin.compliance.subject-access.export` | `admin.compliance.export` |
| GET | `/admin/compliance/audit.csv` | `admin.compliance.audit-export` | `admin.compliance.export` |

---

## Artisan (not HTTP)

| Command | When |
|---------|------|
| `intranet:sync-integrations` | Every 15 minutes (`routes/console.php`) |
| `intranet:gdpr-prune` | Daily 02:30 |
| `intranet:progress` | Manual / CI hygiene |

Livewire components post to Livewire’s own endpoint (`/livewire/...`); they inherit the same session auth as the host page.
