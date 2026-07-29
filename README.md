# OJ Solutions Intranet Portal

Laravel 11 modular monolith for the OJ Solutions staff intranet.

| Doc | Reference |
|-----|-----------|
| Implementation | OJ-INTRA-IMP-001 |
| Requirements | OJ-INTRA-URD-001 |
| Gates | OJ-INTRA-CHK-001 |
| Architecture | OJ-INTRA-ARC-001 |
| Design system | OJ-INTRA-DS-001 (`05_Design_System.html`) |
| Agent skill | `.cursor/skills/oj-intranet/` |

## Stack

PHP 8.3 · Laravel 11 · Livewire · Tailwind (DS-001) · MySQL 8 / SQLite · Fortify · Spatie Permission

**Local runtime:** XAMPP Apache (primary). Docker Compose is optional and not required for development.

## Quick start — XAMPP Apache (under 30 minutes)

### Prerequisites

1. [XAMPP](https://www.apachefriends.org/) with Apache (and MySQL if you prefer it over SQLite)
2. Composer 2 on PATH
3. PHP CLI 8.3+ with `fileinfo`, `pdo_sqlite` or `pdo_mysql`, `mbstring`, `openssl`
4. Node.js 20+ optional (Vite); a CSS fallback ships at `public/css/oj.css`

### Install

```bash
cd C:\xampp\htdocs\intranet
composer install --no-security-blocking
copy .env.example .env
php artisan key:generate
```

Start **Apache** in the XAMPP Control Panel, then open the first-run wizard:

**http://localhost/intranet/install**

The wizard checks PHP extensions and writable paths, configures SQLite or MySQL, runs migrations, and creates your admin account. After that, the installer locks itself (`storage/app/installed`).

### Apache Alias (required for subdirectory URL)

Without the Alias, Apache may not map `/intranet` → `public/`, and you can see Laravel’s **404 | NOT FOUND** on `/install`.

1. In `C:\xampp\apache\conf\httpd.conf`, ensure `LoadModule rewrite_module` is enabled and `Include conf/extra/httpd-vhosts.conf` is active.
2. Append to `C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
Include "C:/xampp/htdocs/intranet/apache/alias.conf"
```

   Edit paths inside [`apache/alias.conf`](apache/alias.conf) if the project is not at `C:/xampp/htdocs/intranet`.
3. Restart Apache.
4. Confirm `APP_URL=http://localhost/intranet` in `.env` (wizard will refresh this later).

**Quick check:** `http://localhost/intranet/up` should return OK (health endpoint). If that 404s, the Alias / rewrite setup is still wrong.

### Seeded demo accounts (optional)

If you prefer CLI setup instead of the wizard:

```bash
php artisan migrate --seed
```

| Account | Password | Role |
|---------|----------|------|
| admin@oj.local | password | Admin |
| staff@oj.local | password | Staff |

### URL options

| Mode | URL | Setup |
|------|-----|--------|
| **Subdirectory (default)** | http://localhost/intranet | Apache Alias in [`apache/alias.conf`](apache/alias.conf) (included from XAMPP `httpd-vhosts.conf`) |
| **Installer** | http://localhost/intranet/install | First-run wizard |
| **Virtual host** | http://intranet.local | See [`apache/vhost.conf`](apache/vhost.conf) |

For the vhost: include that file from XAMPP’s `httpd-vhosts.conf`, add `127.0.0.1 intranet.local` to your hosts file, set `APP_URL=http://intranet.local` in `.env`, restart Apache.

### MySQL (optional)

In XAMPP, start MySQL, create database `oj_intranet`, then in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=oj_intranet
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate:fresh --seed
```

## Quality commands

```bash
vendor/bin/pint
vendor/bin/phpstan analyse -c phpstan.neon
vendor/bin/phpstan analyse -c phpstan-core.neon
vendor/bin/pest
```

## Module layout

```
app/Core/       # Module registry, hooks — no business logic
app/Modules/    # Feature modules (Demo ships for Gate 0)
app/Shared/     # AuditLogger, Settings, shared models
```

Disable a module: set `modules.is_enabled = 0` for that name (e.g. `demo`). Routes and menu entries disappear without errors.

## Optional: Docker Compose

`docker-compose.yml` remains for production-parity stacks (nginx, Redis, Meilisearch, Mailpit). It is **not** required for day-to-day XAMPP development.

## Phase status

**Phase 0 (Foundation)** — local Apache path ready; Gate 0 still has open items (Admin MFA enforce, staging, CI lockfile).

See `.cursor/skills/oj-intranet/reference/phases.md` for the full 8–10 week plan.
