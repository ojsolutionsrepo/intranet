<?php

namespace App\Modules\Directory\Livewire;

use App\Modules\Directory\Services\StaffImportService;
use Livewire\Component;
use Livewire\WithFileUploads;

class StaffImport extends Component
{
    use WithFileUploads;

    public $file = null;

    /**
     * Display-only columns. Full rows (bank details, DOB, next of kin…) are
     * never put in public properties, because Livewire sends those to the browser.
     *
     * @var list<array<string, mixed>>
     */
    public array $previewRows = [];

    /** @var list<array{row: int, message: string}> */
    public array $importErrors = [];

    public bool $hasPreview = false;

    public bool $isFormImport = false;

    public ?string $resultMessage = null;

    public function preview(StaffImportService $importer): void
    {
        $this->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        $result = $importer->preview($this->file);
        $this->previewRows = array_map(
            fn (array $row) => array_intersect_key($row, array_flip(StaffImportService::PREVIEW_COLUMNS)),
            $result['rows'],
        );
        $this->isFormImport = collect($result['rows'])->contains('source', 'form');
        $this->importErrors = $result['errors'];
        $this->hasPreview = true;
        $this->resultMessage = null;
    }

    public function commit(StaffImportService $importer): void
    {
        abort_unless($this->hasPreview && $this->file, 400);

        // Re-parse the uploaded file server-side so sensitive columns never leave the server.
        $stats = $importer->commit($importer->preview($this->file)['rows']);
        $this->resultMessage = sprintf(
            'Import complete: %d created, %d updated, %d skipped.',
            $stats['created'],
            $stats['updated'],
            $stats['skipped'],
        );
        $this->hasPreview = false;
        $this->isFormImport = false;
        $this->previewRows = [];
        $this->importErrors = [];
        $this->file = null;
    }

    public function render()
    {
        return view('directory::livewire.staff-import');
    }
}
