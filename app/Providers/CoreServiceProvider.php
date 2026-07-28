<?php

namespace App\Providers;

use App\Core\Hooks\HookManager;
use App\Core\Modules\ModuleRegistry;
use App\Core\Modules\ModuleServiceProvider;
use App\Modules\Admin\AdminServiceProvider;
use App\Modules\Demo\DemoServiceProvider;
use App\Modules\Directory\DirectoryServiceProvider;
use App\Shared\Adapters\Drive\GoogleDriveOAuthDriver;
use App\Shared\Adapters\Drive\LocalDriveBroker;
use App\Shared\Adapters\Governex\GovernexApiDriver;
use App\Shared\Adapters\Governex\GovernexCsvDriver;
use App\Shared\Adapters\Plane\PlaneFakeDriver;
use App\Shared\Adapters\Plane\PlaneHttpDriver;
use App\Shared\Adapters\Sso\GoogleOidcDriver;
use App\Shared\Adapters\Sso\JwtSsoDriver;
use App\Shared\Adapters\Sso\LocalCredentialsDriver;
use App\Shared\Contracts\DriveBroker;
use App\Shared\Contracts\GovernexAdapter;
use App\Shared\Contracts\PlaneAdapter;
use App\Shared\Contracts\SsoAdapter;
use App\Shared\Contracts\VirusScanner;
use App\Shared\Security\ClamAvScanner;
use App\Shared\Security\NullVirusScanner;
use App\Shared\Services\AuditLogger;
use App\Shared\Services\IntegrationHealthService;
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

        $this->registerIntegrations();

        $this->app->register(ModuleServiceProvider::class);
        $this->app->register(DemoServiceProvider::class);
        $this->app->register(DirectoryServiceProvider::class);
        $this->app->register(AdminServiceProvider::class);
        $this->app->register(\App\Modules\News\NewsServiceProvider::class);
        $this->app->register(\App\Modules\Documents\DocumentsServiceProvider::class);
        $this->app->register(\App\Modules\Documents\PoliciesMenuServiceProvider::class);
        $this->app->register(\App\Modules\Dashboard\DashboardServiceProvider::class);
        $this->app->register(\App\Modules\Calendar\CalendarServiceProvider::class);
        $this->app->register(\App\Modules\Search\SearchServiceProvider::class);
        $this->app->register(\App\Modules\Projects\ProjectsServiceProvider::class);
        $this->app->singleton(\App\Shared\Services\AudienceResolver::class);
        $this->app->singleton(\App\Shared\Services\HtmlSanitizer::class);
        $this->app->singleton(\App\Shared\Services\Analytics::class);
        $this->app->singleton(IntegrationHealthService::class);
        $this->app->singleton(VirusScanner::class, function () {
            if (config('gdpr.scanner') === 'clamav') {
                return new ClamAvScanner(
                    (string) config('gdpr.clamav_host'),
                    (int) config('gdpr.clamav_port'),
                );
            }

            return new NullVirusScanner;
        });
    }

    public function boot(): void
    {
        //
    }

    private function registerIntegrations(): void
    {
        $this->app->singleton(LocalCredentialsDriver::class);
        $this->app->singleton(JwtSsoDriver::class, fn () => new JwtSsoDriver(
            (string) config('integrations.sso.jwt_secret', ''),
        ));
        $this->app->singleton(GoogleOidcDriver::class, fn () => new GoogleOidcDriver(
            config('integrations.sso.google_client_id'),
            config('integrations.sso.google_client_secret'),
        ));

        $this->app->singleton(SsoAdapter::class, function ($app) {
            return match (config('integrations.sso.driver', 'local')) {
                'jwt' => $app->make(JwtSsoDriver::class),
                'google_oidc' => $app->make(GoogleOidcDriver::class),
                default => $app->make(LocalCredentialsDriver::class),
            };
        });

        $this->app->singleton(DriveBroker::class, function () {
            if (filled(config('integrations.drive.client_id'))
                && filled(config('integrations.drive.client_secret'))) {
                return new GoogleDriveOAuthDriver(
                    config('integrations.drive.client_id'),
                    config('integrations.drive.client_secret'),
                );
            }

            return new LocalDriveBroker;
        });

        $this->app->singleton(PlaneAdapter::class, function () {
            if (config('integrations.plane.driver') === 'http'
                && filled(config('integrations.plane.base_url'))
                && filled(config('integrations.plane.api_key'))) {
                return new PlaneHttpDriver(
                    config('integrations.plane.base_url'),
                    config('integrations.plane.api_key'),
                );
            }

            return new PlaneFakeDriver;
        });

        $this->app->singleton(GovernexAdapter::class, function () {
            if (config('integrations.governex.driver') === 'api'
                && filled(config('integrations.governex.base_url'))
                && filled(config('integrations.governex.api_key'))) {
                return new GovernexApiDriver(
                    config('integrations.governex.base_url'),
                    config('integrations.governex.api_key'),
                );
            }

            return new GovernexCsvDriver(config('integrations.governex.csv_path'));
        });
    }
}
