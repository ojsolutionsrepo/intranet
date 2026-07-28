<?php

namespace App\Modules\Dashboard\Livewire\Widgets;

use App\Models\User;
use App\Modules\Calendar\Services\CalendarService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Throwable;

class UpcomingEventsWidget extends Component
{
    public function render(CalendarService $calendar)
    {
        try {
            $user = Auth::user();
            abort_unless($user instanceof User, 403);

            $events = $calendar->upcomingFor($user, 5);

            return view('dashboard::widgets.upcoming-events', [
                'events' => $events,
                'error' => null,
            ]);
        } catch (Throwable $e) {
            report($e);

            return view('dashboard::widgets.upcoming-events', [
                'events' => collect(),
                'error' => 'Calendar temporarily unavailable.',
            ]);
        }
    }
}
