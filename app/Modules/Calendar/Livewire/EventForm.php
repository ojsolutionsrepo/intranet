<?php

namespace App\Modules\Calendar\Livewire;

use App\Modules\Calendar\Services\CalendarService;
use App\Shared\Models\Department;
use Livewire\Component;

class EventForm extends Component
{
    public string $title = '';

    public string $description = '';

    public string $category = 'General';

    public string $starts_at = '';

    public string $ends_at = '';

    public bool $all_day = false;

    public string $location = '';

    /** @var list<string> */
    public array $department_ids = [];

    public function mount(): void
    {
        $this->starts_at = now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i');
        $this->ends_at = now()->addDay()->setTime(11, 0)->format('Y-m-d\TH:i');
    }

    public function save(CalendarService $calendar): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', 'string', 'max:80'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $event = $calendar->create(auth()->user(), [
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'all_day' => $this->all_day,
            'location' => $this->location,
            'audience' => ['departments' => $this->department_ids],
        ]);

        session()->flash('status', 'Event created.');
        $this->redirect(route('calendar.index', ['focus' => $event->starts_at->toDateString()]), navigate: true);
    }

    public function render()
    {
        return view('calendar::livewire.event-form', [
            'categories' => array_keys(CalendarService::CATEGORY_COLOURS),
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }
}
