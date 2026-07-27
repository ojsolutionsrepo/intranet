<?php

namespace App\Core\Modules;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission as PermissionModel;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class);
    }

    public function boot(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);

        foreach ($registry->all() as $name => $manifest) {
            if (! $registry->isEnabled($name)) {
                continue;
            }

            if ($manifest->migrations) {
                $this->loadMigrationsFrom($manifest->migrations);
            }

            if ($manifest->viewsPath && $manifest->viewsNamespace) {
                View::addNamespace($manifest->viewsNamespace, $manifest->viewsPath);
            }
        }

        $this->registerPermissions($registry);
    }

    private function registerPermissions(ModuleRegistry $registry): void
    {
        if (! $this->app->runningInConsole() && ! $this->app->environment('testing')) {
            // Defer permission seeding to seeders; bind gates for registered names.
        }

        foreach ($registry->allPermissions() as $permission => $description) {
            Gate::define($permission, function ($user) use ($permission) {
                if (! is_object($user) || ! method_exists($user, 'hasPermissionTo')) {
                    return false;
                }

                return (bool) $user->hasPermissionTo($permission);
            });
        }
    }

    /**
     * Sync module permissions into the database (call from seeder).
     */
    public static function syncPermissions(ModuleRegistry $registry): void
    {
        foreach ($registry->allPermissions() as $name => $description) {
            PermissionModel::findOrCreate($name);
        }
    }
}
