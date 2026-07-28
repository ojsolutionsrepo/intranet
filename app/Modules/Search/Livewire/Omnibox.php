<?php

namespace App\Modules\Search\Livewire;

use App\Modules\Search\Services\SearchService;
use Livewire\Component;

class Omnibox extends Component
{
    public bool $open = false;

    public string $q = '';

    /** @var list<array<string, mixed>> */
    public array $hits = [];

    public function updatedQ(SearchService $search): void
    {
        if (strlen(trim($this->q)) < 2) {
            $this->hits = [];

            return;
        }

        $this->hits = $search->suggest(auth()->user(), $this->q, 8)->all();
    }

    public function openBox(): void
    {
        $this->open = true;
    }

    public function closeBox(): void
    {
        $this->open = false;
        $this->q = '';
        $this->hits = [];
    }

    public function render()
    {
        return view('search::livewire.omnibox');
    }
}
