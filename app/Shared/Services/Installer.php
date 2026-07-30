<?php

namespace App\Shared\Services;

use App\Core\Modules\ModuleRegistry;
use App\Core\Modules\ModuleServiceProvider;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PDO;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

final class Installer
{
    public const LOCK_FILE = 'installed';

    public static function isInstalled(): bool
    {
        return File::exists(storage_path('app/'.self::LOCK_FILE));
    }

    public function markInstalled(): void
    {
        File::ensureDirectoryExists(storage_path('app'));
        File::put(
            storage_path('app/'.self::LOCK_FILE),
            json_encode([
                'installed_at' => now()->toIso8601String(),
                'version' => '0.1.0',
            ], JSON_PRETTY_PRINT),
        );
    }

    /**
     * Make HTTP bootable before migrations (copy .env, APP_KEY).
     * Safe to call on every pre-install request.
     */
    public function prepareFreshInstall(): void
    {
        if (app()->environment('testing') || self::isInstalled()) {
            return;
        }

        $envPath = base_path('.env');
        if (! File::exists($envPath) && File::exists(base_path('.env.example'))) {
            File::copy(base_path('.env.example'), $envPath);
        }

        if (empty(config('app.key'))) {
            // Avoid Artisan during HTTP boot (can re-enter the container).
            $key = 'base64:'.base64_encode(random_bytes(32));
            config(['app.key' => $key]);
            if (! app()->environment('testing')) {
                $this->writeEnv(['APP_KEY' => $key]);
            }
        }
    }

    /**
     * @return list<array{id: string, label: string, ok: bool, detail: string}>
     */
    public function requirements(): array
    {
        $phpOk = version_compare(PHP_VERSION, '8.2.0', '>=');

        $extensions = [
            'openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo', 'bcmath',
        ];

        $checks = [
            [
                'id' => 'php',
                'label' => 'PHP 8.2+',
                'ok' => $phpOk,
                'detail' => 'Detected '.PHP_VERSION,
            ],
        ];

        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            $checks[] = [
                'id' => 'ext_'.$ext,
                'label' => 'PHP extension: '.$ext,
                'ok' => $loaded,
                'detail' => $loaded ? 'Loaded' : 'Missing',
            ];
        }

        $pdoDrivers = [
            [
                'id' => 'pdo_sqlite',
                'label' => 'PDO SQLite (recommended for XAMPP quick start)',
                'ok' => extension_loaded('pdo_sqlite'),
                'detail' => extension_loaded('pdo_sqlite') ? 'Available' : 'Optional if using MySQL',
            ],
            [
                'id' => 'pdo_mysql',
                'label' => 'PDO MySQL',
                'ok' => extension_loaded('pdo_mysql'),
                'detail' => extension_loaded('pdo_mysql') ? 'Available' : 'Optional if using SQLite',
            ],
        ];

        $checks = array_merge($checks, $pdoDrivers);

        $paths = [
            'storage' => storage_path(),
            'bootstrap/cache' => base_path('bootstrap/cache'),
            '.env' => base_path('.env'),
        ];

        foreach ($paths as $label => $path) {
            $exists = File::exists($path) || File::isDirectory($path);
            $writable = $exists && is_writable($path);
            if ($label === '.env' && ! $exists) {
                $writable = is_writable(base_path());
            }
            $checks[] = [
                'id' => 'path_'.$label,
                'label' => 'Writable: '.$label,
                'ok' => $writable,
                'detail' => $writable ? 'Writable' : ($exists ? 'Not writable' : 'Missing'),
            ];
        }

        $hasDbDriver = extension_loaded('pdo_sqlite') || extension_loaded('pdo_mysql');
        $checks[] = [
            'id' => 'db_driver',
            'label' => 'At least one database driver (SQLite or MySQL)',
            'ok' => $hasDbDriver,
            'detail' => $hasDbDriver ? 'OK' : 'Enable pdo_sqlite or pdo_mysql in php.ini',
        ];

        $checks[] = $this->apacheUrlCheck();

        return $checks;
    }

    /**
     * Soft check: Alias is recommended; /public/ fallback still works after path fix.
     *
     * @return array{id: string, label: string, ok: bool, detail: string, advisory?: bool, hint?: string}
     */
    public function apacheUrlCheck(): array
    {
        $viaPublic = ($_SERVER['OJ_SERVED_VIA_PUBLIC'] ?? '0') === '1';
        $aliasConf = base_path('apache/alias.conf');
        $includeLine = 'Include "'.str_replace('\\', '/', $aliasConf).'"';

        if ($viaPublic) {
            return [
                'id' => 'apache_alias',
                'label' => 'Apache Alias → public/ (recommended)',
                'ok' => true,
                'advisory' => true,
                'detail' => 'Serving via /public/ fallback. Install will work; add the Alias for cleaner URLs and fewer rewrite edge cases.',
                'hint' => $includeLine,
            ];
        }

        return [
            'id' => 'apache_alias',
            'label' => 'Apache Alias → public/ (recommended)',
            'ok' => true,
            'advisory' => true,
            'detail' => 'URL mapping looks healthy (Alias or equivalent).',
            'hint' => $includeLine,
        ];
    }

    public function requirementsPassed(): bool
    {
        foreach ($this->requirements() as $check) {
            if (in_array($check['id'], ['pdo_sqlite', 'pdo_mysql', 'apache_alias'], true)) {
                continue;
            }
            if (! empty($check['advisory'])) {
                continue;
            }
            if (! $check['ok']) {
                return false;
            }
        }

        return extension_loaded('pdo_sqlite') || extension_loaded('pdo_mysql');
    }

    /**
     * @param  array{connection: string, host?: string, port?: string, database?: string, username?: string, password?: string}  $config
     */
    public function testDatabaseConnection(array $config): void
    {
        if (app()->environment('testing')) {
            return;
        }

        if ($config['connection'] === 'sqlite') {
            $path = $config['database'] ?: database_path('database.sqlite');
            if ($path !== ':memory:' && ! File::exists($path)) {
                File::ensureDirectoryExists(dirname($path));
                File::put($path, '');
            }

            new PDO('sqlite:'.($path === ':memory:' ? ':memory:' : $path));

            return;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s',
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? '3306',
        );

        $pdo = new PDO(
            $dsn,
            $config['username'] ?? 'root',
            $config['password'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );

        $database = $config['database'] ?? 'oj_intranet';
        $quoted = str_replace('`', '``', $database);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$quoted}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$quoted}`");
    }

    /**
     * @param  array{connection: string, host?: string, port?: string, database?: string, username?: string, password?: string}  $config
     */
    public function applyDatabaseConfig(array $config): void
    {
        if (app()->environment('testing')) {
            return;
        }

        if ($config['connection'] === 'sqlite') {
            $database = $config['database'] ?: database_path('database.sqlite');
            if ($database !== ':memory:' && ! File::exists($database)) {
                File::ensureDirectoryExists(dirname($database));
                File::put($database, '');
            }

            $this->writeEnv([
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => $database,
                'DB_HOST' => '',
                'DB_PORT' => '',
                'DB_USERNAME' => '',
                'DB_PASSWORD' => '',
            ]);

            config([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => $database,
            ]);
        } else {
            $this->writeEnv([
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $config['host'] ?? '127.0.0.1',
                'DB_PORT' => $config['port'] ?? '3306',
                'DB_DATABASE' => $config['database'] ?? 'oj_intranet',
                'DB_USERNAME' => $config['username'] ?? 'root',
                'DB_PASSWORD' => $config['password'] ?? '',
            ]);

            config([
                'database.default' => 'mysql',
                'database.connections.mysql.host' => $config['host'] ?? '127.0.0.1',
                'database.connections.mysql.port' => $config['port'] ?? '3306',
                'database.connections.mysql.database' => $config['database'] ?? 'oj_intranet',
                'database.connections.mysql.username' => $config['username'] ?? 'root',
                'database.connections.mysql.password' => $config['password'] ?? '',
            ]);
        }

        DB::purge();
        DB::reconnect();
    }

    public function ensureAppKey(): void
    {
        if (! empty(config('app.key'))) {
            return;
        }

        if (app()->environment('testing')) {
            config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

            return;
        }

        Artisan::call('key:generate', ['--force' => true]);
    }

    public function migrate(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        Artisan::call('migrate', ['--force' => true]);

        // Switch to durable drivers now that tables exist.
        $this->writeEnv([
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'database',
            'QUEUE_CONNECTION' => 'database',
        ]);
        config([
            'session.driver' => 'database',
            'cache.default' => 'database',
            'queue.default' => 'database',
        ]);
    }

    /**
     * @param  array{name: string, email: string, password: string, site_name?: string}  $admin
     */
    public function seedFoundation(array $admin): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['Admin', 'Manager', 'Staff', 'Guest'] as $role) {
            Role::findOrCreate($role);
        }

        $registry = app(ModuleRegistry::class);
        ModuleServiceProvider::syncPermissions($registry);
        $permissionNames = array_keys($registry->allPermissions());

        Role::findByName('Admin')->syncPermissions($permissionNames);
        Role::findByName('Manager')->syncPermissions(
            collect($permissionNames)->reject(fn (string $key) => str_starts_with($key, 'admin.'))->all()
        );
        Role::findByName('Staff')->syncPermissions([
            'demo.view',
            'directory.view',
            'news.view',
            'documents.view',
            'policies.view',
            'dashboard.view',
            'calendar.view',
            'search.view',
            'projects.view',
        ]);
        Role::findByName('Guest')->syncPermissions([]);

        DB::table('modules')->updateOrInsert(
            ['name' => 'demo'],
            [
                'version' => '1.0.0',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $settings = app(Settings::class);
        $settings->set('session_idle_timeout', 480, 'auth');
        $settings->set('site_name', $admin['site_name'] ?? 'OJ Intranet', 'branding');

        $user = User::query()->updateOrCreate(
            ['email' => $admin['email']],
            [
                'name' => $admin['name'],
                'password' => Hash::make($admin['password']),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
        $user->syncRoles(['Admin']);

        app(AuditLogger::class)->log('install.completed', $user, null, [
            'email' => $user->email,
        ], $user->id);
    }

    /**
     * Optional demo content (directory, news, docs, calendar, projects).
     * Does not overwrite the admin account created during install.
     */
    public function seedDemo(): void
    {
        Artisan::call('db:seed', [
            '--class' => \Database\Seeders\DemoContentSeeder::class,
            '--force' => true,
        ]);
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function writeEnv(array $values): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $path = base_path('.env');
        if (! File::exists($path)) {
            File::copy(base_path('.env.example'), $path);
        }

        $content = File::get($path);

        foreach ($values as $key => $value) {
            $value ??= '';
            $escaped = $this->escapeEnvValue($value);
            $pattern = "/^{$key}=.*/m";

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $key.'='.$escaped, $content) ?? $content;
            } else {
                $content = rtrim($content).PHP_EOL.$key.'='.$escaped.PHP_EOL;
            }
        }

        File::put($path, $content);
    }

    public function setAppUrlFromRequest(string $url): void
    {
        $this->writeEnv(['APP_URL' => rtrim($url, '/')]);
        config(['app.url' => rtrim($url, '/')]);
    }

    private function escapeEnvValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/\s|#|"|\'/', $value)) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }

    public function failureMessage(Throwable $e): string
    {
        return Str::limit($e->getMessage(), 400);
    }
}
