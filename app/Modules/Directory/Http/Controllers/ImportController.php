<?php

namespace App\Modules\Directory\Http\Controllers;

use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use App\Modules\Directory\Services\StaffImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Staff import uses a classic multipart form (not Livewire temporary uploads):
 * the production host disables tmpfile()/fileinfo, which Livewire uploads need.
 * See docs/ops/deploy-runbook.md → "Branding uploads … on restricted hosts".
 *
 * Flow: upload → file kept privately on the "local" disk, keyed in the session
 * → preview page (with per-row account matching) → commit or cancel.
 */
class ImportController extends Controller
{
    private const SESSION_KEY = 'staff_import';

    private const DIR = 'staff-imports';

    public function index(ModuleRegistry $registry, StaffImportService $importer): View
    {
        abort_unless($registry->isEnabled('directory'), 404);

        $pending = $this->pendingFile();
        $preview = null;

        if ($pending) {
            try {
                $preview = $importer->preview($pending);
            } catch (\Throwable $e) {
                $this->clearPending();
                session()->now('import_error', 'Could not read that file: '.$e->getMessage());
            }
        }

        $rows = $preview['rows'] ?? [];

        return view('directory::import', [
            'hasPreview' => $preview !== null,
            'fileName' => session(self::SESSION_KEY.'.name'),
            // Only display columns reach the page; bank details etc. stay server-side.
            'previewRows' => array_map(
                fn (array $row) => array_intersect_key($row, array_flip(StaffImportService::PREVIEW_COLUMNS)),
                $rows,
            ),
            'importErrors' => $preview['errors'] ?? [],
            'isFormImport' => collect($rows)->contains('source', 'form'),
            'users' => $preview !== null
                ? User::query()->orderBy('name')->get(['id', 'name', 'email'])
                : collect(),
        ]);
    }

    public function preview(ModuleRegistry $registry, Request $request): RedirectResponse|JsonResponse
    {
        abort_unless($registry->isEnabled('directory'), 404);

        $request->validate([
            // "extensions" checks the file name only; "mimes" needs fileinfo, which the host disables.
            'file' => ['required', 'file', 'max:10240', 'extensions:csv,txt,xlsx,xls'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $token = (string) Str::uuid();

        $this->clearPending();
        Storage::disk('local')->put(self::DIR."/{$token}.{$ext}", (string) file_get_contents($file->getRealPath()));

        session()->put(self::SESSION_KEY, [
            'token' => $token,
            'ext' => $ext,
            'name' => $file->getClientOriginalName(),
        ]);

        // The page uploads in the background (with a progress bar) and asks for JSON.
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'next' => route('directory.import')]);
        }

        return redirect()->route('directory.import');
    }

    public function commit(ModuleRegistry $registry, Request $request, StaffImportService $importer): RedirectResponse
    {
        abort_unless($registry->isEnabled('directory'), 404);

        $validated = $request->validate([
            'mappings' => ['array'],
            'mappings.*' => ['string', 'regex:/^(new|skip|\d+)$/'],
        ]);

        $pending = $this->pendingFile();
        if (! $pending) {
            return redirect()->route('directory.import')
                ->with('import_error', 'The uploaded file has expired. Please upload it again.');
        }

        $mappings = [];
        foreach ($validated['mappings'] ?? [] as $index => $value) {
            $mappings[(int) $index] = ctype_digit($value) ? (int) $value : $value;
        }

        $stats = $importer->commit($importer->preview($pending)['rows'], $mappings);
        $this->clearPending();

        return redirect()->route('directory.import')->with('status', sprintf(
            'Import complete: %d created, %d updated, %d skipped.',
            $stats['created'],
            $stats['updated'],
            $stats['skipped'],
        ));
    }

    public function cancel(ModuleRegistry $registry): RedirectResponse
    {
        abort_unless($registry->isEnabled('directory'), 404);

        $this->clearPending();

        return redirect()->route('directory.import');
    }

    private function pendingFile(): ?UploadedFile
    {
        $pending = session(self::SESSION_KEY);
        if (! is_array($pending) || ! isset($pending['token'], $pending['ext'])) {
            return null;
        }

        $disk = Storage::disk('local');
        $path = self::DIR."/{$pending['token']}.{$pending['ext']}";
        if (! $disk->exists($path)) {
            return null;
        }

        // test=true: treat as an already-validated local file (no PHP upload check).
        return new UploadedFile($disk->path($path), (string) ($pending['name'] ?? basename($path)), null, null, true);
    }

    private function clearPending(): void
    {
        $pending = session(self::SESSION_KEY);
        if (is_array($pending) && isset($pending['token'], $pending['ext'])) {
            Storage::disk('local')->delete(self::DIR."/{$pending['token']}.{$pending['ext']}");
        }
        session()->forget(self::SESSION_KEY);
    }
}
