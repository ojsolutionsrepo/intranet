<?php

namespace App\Modules\Directory\Livewire;

use App\Modules\Directory\Services\DirectorySearch;
use App\Shared\Models\Department;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class DirectoryIndex extends Component
{
    use WithPagination;

    public string $q = '';

    public string $department_id = '';

    public string $role = '';

    public string $expertise = '';

    public string $viewMode = 'cards';

    protected $queryString = [
        'q' => ['except' => ''],
        'department_id' => ['except' => ''],
        'role' => ['except' => ''],
        'expertise' => ['except' => ''],
        'viewMode' => ['except' => 'cards'],
    ];

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingDepartmentId(): void
    {
        $this->resetPage();
    }

    public function updatingRole(): void
    {
        $this->resetPage();
    }

    public function updatingExpertise(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['q', 'department_id', 'role', 'expertise']);
        $this->resetPage();
    }

    public function render(DirectorySearch $search)
    {
        $results = $search->search([
            'q' => $this->q,
            'department_id' => $this->department_id !== '' ? (int) $this->department_id : null,
            'role' => $this->role !== '' ? $this->role : null,
            'expertise' => $this->expertise !== '' ? $this->expertise : null,
        ]);

        return view('directory::livewire.index', [
            'people' => $results,
            'departments' => Department::query()->orderBy('order')->orderBy('name')->get(),
            'roles' => Role::query()->orderBy('name')->pluck('name'),
            'expertiseTags' => $search->allExpertiseTags(),
        ]);
    }
}
