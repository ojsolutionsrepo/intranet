<?php

namespace App\Providers;

use App\Shared\Services\Installer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Installer::class);
    }

    public function boot(): void
    {
        if (Installer::isInstalled() || $this->app->environment('testing')) {
            return;
        }

        // Pre-migrate defaults so the installer UI can load on a fresh machine.
        config([
            'session.driver' => 'file',
            'cache.default' => 'file',
            'queue.default' => 'sync',
        ]);

        if (! $this->app->runningInConsole()) {
            app(Installer::class)->prepareFreshInstall();
        }
    }
}
