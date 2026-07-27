<?php

namespace App\Modules\Documents\Livewire;

use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentCategory;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentBrowser extends Component
{
    use WithPagination;

    public string $q = '';

    public string $category_id = '';

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $query = Document::query()
            ->with(['category', 'owner', 'currentVersion'])
            ->notTrashed()
            ->when($this->category_id !== '', fn ($q) => $q->where('category_id', $this->category_id))
            ->when($this->q !== '', function ($q): void {
                $q->where(function ($inner): void {
                    $inner->where('title', 'like', '%'.$this->q.'%')
                        ->orWhereHas('currentVersion', fn ($v) => $v->where('original_filename', 'like', '%'.$this->q.'%'));
                });
            })
            ->orderBy('title');

        $docs = $query->get()->filter(fn (Document $doc) => $doc->isVisibleTo($user))->values();
        $page = max(1, (int) $this->getPage());
        $perPage = 20;
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $docs->forPage($page, $perPage)->values(),
            $docs->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );

        return view('documents::livewire.browser', [
            'documents' => $paginator,
            'categories' => DocumentCategory::query()->orderBy('order')->orderBy('name')->get(),
        ]);
    }
}
