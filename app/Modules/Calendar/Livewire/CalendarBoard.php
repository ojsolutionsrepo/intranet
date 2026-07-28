<?php

namespace App\Modules\Calendar\Livewire;

use App\Modules\Calendar\Services\CalendarService;
use Carbon\Carbon;
use Livewire\Component;

class CalendarBoard extends Component
{
    public string $viewMode = 'month'; // month|week|list

    public string $focus = '';

    public string $category = '';

    public function mount(): void
    {
        $this->focus = request('focus', now()->toDateString());
    }

    public function previous(): void
    {
        $date = Carbon::parse($this->focus);
        $this->focus = match ($this->viewMode) {
            'week' => $date->subWeek()->toDateString(),
            default => $date->subMonth()->toDateString(),
        };
    }

    public function next(): void
    {
        $date = Carbon::parse($this->focus);
        $this->focus = match ($this->viewMode) {
            'week' => $date->addWeek()->toDateString(),
            default => $date->addMonth()->toDateString(),
        };
    }

    public function render(CalendarService $calendar)
    {
        $focus = Carbon::parse($this->focus ?: now()->toDateString());

        [$from, $to] = match ($this->viewMode) {
            'week' => [$focus->copy()->startOfWeek(), $focus->copy()->endOfWeek()],
            'list' => [$focus->copy()->startOfMonth(), $focus->copy()->addMonths(2)->endOfMonth()],
            default => [$focus->copy()->startOfMonth()->startOfWeek(), $focus->copy()->endOfMonth()->endOfWeek()],
        };

        $events = $calendar->eventsFor(
            auth()->user(),
            $from,
            $to,
            $this->category !== '' ? $this->category : null,
        );

        return view('calendar::livewire.board', [
            'focusDate' => $focus,
            'from' => $from,
            'to' => $to,
            'events' => $events,
            'categories' => array_keys(CalendarService::CATEGORY_COLOURS),
            'colours' => CalendarService::CATEGORY_COLOURS,
        ]);
    }
}
