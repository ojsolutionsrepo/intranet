# Developer handbook

How to change the OJ Solutions Intranet without breaking module boundaries, ACL, or the design system.

Read [TECHNICAL.md](TECHNICAL.md) for the as-built architecture. This page is the **how**.

---

## 1. Before you write code

1. Trace the work to a `UR-*` ID in `.cursor/skills/oj-intranet/reference/requirements-map.md`.
2. Confirm the phase task in `reference/phases.md`.
3. Place code in `app/Modules/{Name}/` (feature) or `app/Shared/` (cross-cutting) or `app/Core/` (registry/hooks only — **no business logic**).
4. Use `AudienceResolver`, policies, and `AuditLogger`. Do not invent a second ACL.
5. Talk to Google, Plane, or Governex **only** through the adapter interfaces in `app/Shared/Contracts` (or `DocumentStorageAdapter`).
6. Use DS-001 tokens (`oj-*`, `ink-*`, `paper-*`, `ok`/`warn`/`err`). Do not hard-code hex colours.
7. Add a Pest feature test that covers the acceptance criteria.
8. Run Pint, PHPStan, and Pest (see [§8](#8-quality-commands)).

---

## 2. Layering

| Layer | Lives in | Allowed to |
|-------|----------|------------|
| HTTP | `Http/Controllers`, Livewire | Auth, validation, call a service, return a view |
| Service | `Services/` | Transactions, audit, adapters, domain rules |
| Policy | `Policies/` | `view` / `manage` / … using permissions + `isVisibleTo` |
| Model | `Models/` | Relations, casts, `isVisibleTo()` via `AudienceResolver` |
| Adapter | `app/Shared/Adapters` | One vendor; implements a contract |

Controllers should stay thin (target: a handful of lines per action). Form Requests for non-trivial validation (see `StoreStaffInformationRequest`).

---

## 3. Adding a feature module

Copy the **Demo** module as the smallest working example (`app/Modules/Demo`).

### 3.1 Files

```text
app/Modules/Widgets/
├── module.json
├── WidgetsServiceProvider.php
├── Http/Controllers/WidgetController.php
├── Services/WidgetService.php
├── Models/Widget.php          # optional
├── Policies/WidgetPolicy.php  # if the model is audience-scoped
├── Livewire/WidgetIndex.php
└── Resources/views/
    ├── index.blade.php
    └── livewire/index.blade.php
```

`module.json` is metadata only. The registry does **not** load it.

### 3.2 Register the provider

Add the class to `app/Providers/CoreServiceProvider::register()` after the other `$this->app->register(...)` calls. Modules are not auto-discovered.

### 3.3 Service provider pattern

```php
public function boot(ModuleRegistry $registry): void
{
    $registry->register('widgets')
        ->permissions([
            'widgets.view' => 'View widgets',
            'widgets.manage' => 'Create and edit widgets',
        ])
        ->menu(fn () => [
            'label' => 'Widgets',
            'icon' => 'squares',
            'route' => 'widgets.index',
            'permission' => 'widgets.view',
            'order' => 45,
        ])
        ->views(__DIR__.'/Resources/views', 'widgets');

    View::addNamespace('widgets', __DIR__.'/Resources/views');
    Livewire::component('widgets.index', WidgetIndex::class);
    Gate::policy(Widget::class, WidgetPolicy::class);

    Route::middleware(['web', 'auth', 'session.active'])
        ->prefix('widgets')
        ->group(function (): void {
            Route::get('/', [WidgetController::class, 'index'])
                ->name('widgets.index')
                ->middleware('can:widgets.view');
        });
}
```

Enable the module in a seeder (`modules` table `is_enabled = 1`). Sync permissions via `ModuleServiceProvider::syncPermissions` (already called from `RoleSeeder`). Then grant the new keys to Admin (all) / Manager (non-`admin.*`) / Staff (explicit allow-list — **Staff will not see the module until you add `widgets.view` to that list**).

### 3.4 Disable behaviour

Setting `modules.is_enabled = 0`:

- Drops the menu item and that module’s permissions from `allPermissions()`.
- Controllers should `abort_unless($registry->isEnabled('widgets'), 404)`.
- Routes are still registered at boot unless you abort inside the closure (Demo does this).

Clear enablement cache: `ModuleRegistry::forgetCache('widgets')`.

---

## 4. Audience and policies

Content that can be limited to departments, teams, roles, or named users **must** store:

```php
'audience' => app(AudienceResolver::class)->normalize($request->input('audience', [])),
```

Empty audience = company-wide. Visibility:

```php
public function isVisibleTo(User $user): bool
{
    return app(AudienceResolver::class)->allows($this->audience, $user);
}
```

Policies combine a Spatie permission with `isVisibleTo()`. Search must apply the same filter at query time (`SearchService`), never “return the row then hide it in Blade”.

---

## 5. Audit logging

Models that represent durable business records should use the `Auditable` trait (`created` / `updated` / `deleted`).

Explicit actions (login, download, permission change, GDPR export) use:

```php
app(AuditLogger::class)->log('document.downloaded', $document, null, [
    'version' => $version->version_number,
]);
```

Action names are dotted lowercase (`directory.profile_viewed`, `staff_information.updated`). Keep them stable — compliance CSV and UAT scripts depend on them.

---

## 6. Adapters and resilience

To add an external system:

1. Define or extend a contract in `app/Shared/Contracts`.
2. Implement a real driver and a fake/CSV/local driver.
3. Bind in `CoreServiceProvider::registerIntegrations()` from `config/integrations.php`.
4. Wrap outbound calls with `CircuitBreaker` (retry 3×, open after 5 failures, half-open after 60s).
5. Surface health on Admin → Integrations via `IntegrationHealth`.

Never import a vendor SDK from a feature module.

---

## 7. UI

- Extend `resources/views/layouts/app.blade.php` only for chrome that every module needs.
- Module views: `@extends('layouts.app')` and Tailwind classes that reference DS tokens (`bg-paper-0`, `text-ink-900`, `bg-oj-600`, `rounded-oj`, …).
- Register Livewire in the module provider: `Livewire::component('directory.index', DirectoryIndex::class)`.
- Sanitize any rich text with `HtmlSanitizer` before persist/render (News already does this).

---

## 8. Quality commands

```bash
vendor/bin/pint
vendor/bin/phpstan analyse -c phpstan.neon          # app, level 6
vendor/bin/phpstan analyse -c phpstan-core.neon     # app/Core, level 8
vendor/bin/pest
php artisan intranet:progress --write
```

Feature tests use `RefreshDatabase` and `withoutVite()` (`tests/Pest.php`). Seed roles (and module seeders as needed) in `beforeEach`. Prefer `actingAs($user)->get(route('…'))` over hitting URLs as strings.

Filesystem assertions: use the contract method `exists()`, not `assertExists()` on `Storage::disk()` — Intelephense types `disk()` as `Filesystem`, which does not declare `assertExists()`.

```php
expect(Storage::disk('public')->exists($path))->toBeTrue();
```

CI runs Pint `--test`, both PHPStan configs, `npm run build`, and Pest on SQLite memory (`.github/workflows/ci.yml`).

---

## 9. Database

- Reversible migrations under `database/migrations/` (timestamp prefix).
- Encrypted columns: Laravel `encrypted` cast (`APP_KEY`). Used today for staff bank fields and Drive tokens.
- Do not add business tables to Core. Module-owned tables are fine in the global migrations folder (this repo does not yet split migrations per module directory).

---

## 10. Local URLs

Apache Alias: `http://localhost/intranet/...` with `APP_URL=http://localhost/intranet`.

Named routes ignore the subdirectory; `route('directory.index')` is correct. Do not hard-code `/directory`.

First-run: `http://localhost/intranet/install`. Health: `http://localhost/intranet/up`.
