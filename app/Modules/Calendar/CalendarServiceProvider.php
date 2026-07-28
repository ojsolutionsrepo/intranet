<?php

namespace App\Modules\Calendar;

use App\Core\Modules\ModuleRegistry;
use App\Modules\Calendar\Http\Controllers\CalendarController;
use App\Modules\Calendar\Http\Controllers\IcsController;
use App\Modules\Calendar\Livewire\CalendarBoard;
use App\Modules\Calendar\Livewire\EventForm;
use App\Modules\Calendar\Models\CalendarEvent;
use App\Modules\Calendar\Policies\CalendarEventPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class CalendarServiceProvider extends ServiceProvider
{
    public function boot(ModuleRegistry $registry): void
    {
        $registry->register('calendar')
            ->permissions([
                'calendar.view' => 'View calendar events',
                'calendar.manage' => 'Create and edit calendar events',
            ])
            ->menu(fn () => [
                'label' => 'Calendar',
                'icon' => 'calendar',
                'route' => 'calendar.index',
                'permission' => 'calendar.view',
                'order' => 40,
            ])
            ->views(__DIR__.'/Resources/views', 'calendar');

        View::addNamespace('calendar', __DIR__.'/Resources/views');
        Gate::policy(CalendarEvent::class, CalendarEventPolicy::class);

        Livewire::component('calendar.board', CalendarBoard::class);
        Livewire::component('calendar.event-form', EventForm::class);

        Route::middleware(['web', 'auth', 'session.active'])
            ->prefix('calendar')
            ->group(function (): void {
                Route::get('/', [CalendarController::class, 'index'])
                    ->name('calendar.index')
                    ->middleware('can:calendar.view');

                Route::get('/create', [CalendarController::class, 'create'])
                    ->name('calendar.create')
                    ->middleware('can:calendar.manage');

                Route::get('/export.ics', [IcsController::class, 'download'])
                    ->name('calendar.ics.download')
                    ->middleware('can:calendar.view');

                Route::post('/feed-token', [IcsController::class, 'token'])
                    ->name('calendar.ics.token')
                    ->middleware('can:calendar.view');
            });

        // Public personal feed (token auth, no session).
        Route::get('/calendar/feed/{token}.ics', [IcsController::class, 'feed'])
            ->name('calendar.ics.feed');
    }
}
