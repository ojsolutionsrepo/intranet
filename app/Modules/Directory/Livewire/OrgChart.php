<?php

namespace App\Modules\Directory\Livewire;

use App\Models\User;
use Livewire\Component;

class OrgChart extends Component
{
    /** @var array<int, bool> */
    public array $expanded = [];

    public function mount(): void
    {
        $roots = User::query()
            ->whereNull('manager_id')
            ->where('is_active', true)
            ->pluck('id');

        foreach ($roots as $id) {
            $this->expanded[(int) $id] = true;
        }
    }

    public function toggle(int $userId): void
    {
        $this->expanded[$userId] = ! ($this->expanded[$userId] ?? false);
    }

    public function render()
    {
        $people = User::query()
            ->with(['profile', 'departments', 'directReports'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $roots = $people->whereNull('manager_id')->values();

        return view('directory::livewire.org-chart', [
            'roots' => $roots,
            'people' => $people,
        ]);
    }
}
