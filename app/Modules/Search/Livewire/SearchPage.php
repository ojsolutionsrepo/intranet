<?php

namespace App\Modules\Search\Livewire;

use App\Modules\Search\Services\SearchService;
use App\Shared\Models\Department;
use Livewire\Component;

class SearchPage extends Component
{
    public string $q = '';

    public string $type = '';

    public string $department_id = '';

    public function mount(): void
    {
        $this->q = (string) request('q', '');
        $this->type = (string) request('type', '');
        $this->department_id = (string) request('department_id', '');
    }

    public function render(SearchService $search)
    {
        $result = [
            'hits' => collect(),
            'facets' => ['type' => [], 'department' => []],
            'took_ms' => 0,
        ];

        if (trim($this->q) !== '') {
            $result = $search->search(auth()->user(), $this->q, [
                'type' => $this->type !== '' ? $this->type : null,
                'department_id' => $this->department_id !== '' ? (int) $this->department_id : null,
            ]);
        }

        return view('search::livewire.page', [
            'hits' => $result['hits'],
            'facets' => $result['facets'],
            'tookMs' => $result['took_ms'],
            'types' => SearchService::TYPES,
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }
}
