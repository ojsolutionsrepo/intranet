<?php

namespace App\Modules\Documents\Livewire;

use App\Modules\Documents\Models\DocumentCategory;
use App\Modules\Documents\Services\DocumentService;
use App\Shared\Models\Department;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentUpload extends Component
{
    use WithFileUploads;

    public string $title = '';

    public string $category_id = '';

    public string $visibility = 'inherit';

    /** @var list<string> */
    public array $department_ids = [];

    public bool $is_policy = false;

    public bool $mandatory_ack = false;

    public string $review_at = '';

    public string $changelog = 'Initial upload';

    public $file = null;

    public ?string $duplicateWarning = null;

    public function save(DocumentService $documents): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:200'],
            'category_id' => ['required', 'exists:document_categories,id'],
            'file' => ['required', 'file', 'max:20480'],
            'visibility' => ['required', 'in:inherit,all,department,team,users'],
        ]);

        $result = $documents->upload(auth()->user(), [
            'title' => $this->title,
            'category_id' => (int) $this->category_id,
            'visibility' => $this->visibility,
            'audience' => ['departments' => $this->department_ids],
            'is_policy' => $this->is_policy,
            'mandatory_ack' => $this->mandatory_ack,
            'review_at' => $this->review_at !== '' ? $this->review_at : null,
            'changelog' => $this->changelog,
        ], $this->file);

        if ($result['duplicate_warning']) {
            session()->flash('status', 'Uploaded with duplicate checksum warning — same file already exists as another document version (owner: '.$result['duplicate_warning']->document?->owner?->name.').');
        } else {
            session()->flash('status', 'Document uploaded.');
        }

        $this->redirect(route('documents.show', $result['document']), navigate: true);
    }

    public function render()
    {
        return view('documents::livewire.upload', [
            'categories' => DocumentCategory::query()->orderBy('order')->orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }
}
