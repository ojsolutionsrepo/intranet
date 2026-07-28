<?php

namespace App\Modules\Dashboard;

use App\Core\Modules\ModuleRegistry;
use App\Modules\Dashboard\Livewire\DashboardShell;
use App\Modules\Dashboard\Livewire\Widgets\AcknowledgementsWidget;
use App\Modules\Dashboard\Livewire\Widgets\AnnouncementsWidget;
use App\Modules\Dashboard\Livewire\Widgets\MyDocumentsWidget;
use App\Modules\Dashboard\Livewire\Widgets\MyProjectsWidget;
use App\Modules\Dashboard\Livewire\Widgets\NewJoinersWidget;
use App\Modules\Dashboard\Livewire\Widgets\QuickLinksWidget;
use App\Modules\Dashboard\Livewire\Widgets\UpcomingEventsWidget;
use App\Modules\Dashboard\Services\WidgetRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WidgetRegistry::class);
    }

    public function boot(ModuleRegistry $registry): void
    {
        $registry->register('dashboard')
            ->permissions([
                'dashboard.view' => 'View the personalised dashboard',
            ])
            ->menu(fn () => null)
            ->views(__DIR__.'/Resources/views', 'dashboard');

        View::addNamespace('dashboard', __DIR__.'/Resources/views');

        Livewire::component('dashboard.shell', DashboardShell::class);
        Livewire::component('dashboard.widgets.announcements', AnnouncementsWidget::class);
        Livewire::component('dashboard.widgets.my-documents', MyDocumentsWidget::class);
        Livewire::component('dashboard.widgets.upcoming-events', UpcomingEventsWidget::class);
        Livewire::component('dashboard.widgets.acknowledgements', AcknowledgementsWidget::class);
        Livewire::component('dashboard.widgets.quick-links', QuickLinksWidget::class);
        Livewire::component('dashboard.widgets.my-projects', MyProjectsWidget::class);
        Livewire::component('dashboard.widgets.new-joiners', NewJoinersWidget::class);

        // Dashboard route stays in web.php; shell is rendered from the view.
        Route::middleware(['web', 'auth', 'session.active'])
            ->get('/dashboard/prefs', fn () => view('dashboard::prefs'))
            ->name('dashboard.prefs')
            ->middleware('can:dashboard.view');
    }
}
