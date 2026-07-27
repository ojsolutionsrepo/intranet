<?php

namespace App\Providers;

use App\Core\Hooks\HookManager;
use App\Core\Modules\ModuleRegistry;
use App\Core\Modules\ModuleServiceProvider;
use App\Modules\Admin\AdminServiceProvider;
use App\Modules\Demo\DemoServiceProvider;
use App\Modules\Directory\DirectoryServiceProvider;
use App\Shared\Services\AuditLogger;
use App\Shared\Services\Settings;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HookManager::class);
        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(Settings::class);

        $this->app->register(ModuleServiceProvider::class);
        $this->app->register(DemoServiceProvider::class);
        $this->app->register(DirectoryServiceProvider::class);
        $this->app->register(AdminServiceProvider::class);
        $this->app->register(\App\Modules\News\NewsServiceProvider::class);
        $this->app->register(\App\Modules\Documents\DocumentsServiceProvider::class);
        $this->app->register(\App\Modules\Documents\PoliciesMenuServiceProvider::class);
        $this->app->singleton(\App\Shared\Services\AudienceResolver::class);
        $this->app->singleton(\App\Shared\Services\HtmlSanitizer::class);
    }

    public function boot(): void
    {
        //
    }
}
