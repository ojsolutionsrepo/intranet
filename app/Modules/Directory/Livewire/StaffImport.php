<?php

namespace App\Modules\Directory\Livewire;

use App\Modules\Directory\Services\StaffImportService;
use Livewire\Component;
use Livewire\WithFileUploads;

class StaffImport extends Component
{
    use WithFileUploads;

    public $file = null;

    /** @var list<array<string, mixed>> */
    public array $previewRows = [];

    /** @var list<array{row: int, message: string}> */
    public array $errors = [];

    public bool $hasPreview = false;

    public ?string $resultMessage = null;

    public function preview(StaffImportService $importer): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        $result = $importer->preview($this->file);
        $this->previewRows = $result['rows'];
        $this->errors = $result['errors'];
        $this->hasPreview = true;
        $this->resultMessage = null;
    }

    public function commit(StaffImportService $importer): void
    {
        abort_unless($this->hasPreview, 400);

        $stats = $importer->commit($this->previewRows);
        $this->resultMessage = sprintf(
            'Import complete: %d created, %d updated, %d skipped.',
            $stats['created'],
            $stats['updated'],
            $stats['skipped'],
        );
        $this->hasPreview = false;
        $this->previewRows = [];
        $this->errors = [];
        $this->file = null;
    }

    public function render()
    {
        return view('directory::livewire.staff-import');
    }
}
