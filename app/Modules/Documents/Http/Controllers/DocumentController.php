<?php

namespace App\Modules\Documents\Http\Controllers;

use App\Core\Modules\ModuleRegistry;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Models\DocumentVersion;
use App\Modules\Documents\Services\DocumentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('documents'), 404);

        return view('documents::index');
    }

    public function upload(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('documents'), 404);

        return view('documents::upload');
    }

    public function search(ModuleRegistry $registry, Request $request, DocumentService $documents): View
    {
        abort_unless($registry->isEnabled('documents'), 404);

        $q = trim((string) $request->query('q', ''));
        $results = $q !== '' ? $documents->searchBody($q, $request->user()) : collect();

        return view('documents::search', compact('q', 'results'));
    }

    public function show(ModuleRegistry $registry, Document $document): View
    {
        abort_unless($registry->isEnabled('documents'), 404);
        Gate::authorize('view', $document);

        $document->load(['versions.uploader', 'currentVersion', 'category.parent', 'owner']);

        return view('documents::show', compact('document'));
    }

    public function download(ModuleRegistry $registry, Document $document, DocumentService $documents): StreamedResponse
    {
        abort_unless($registry->isEnabled('documents'), 404);
        Gate::authorize('download', $document);

        $version = $document->currentVersion;
        abort_unless($version, 404);

        $binary = $documents->download($document, $version, request()->user());

        return response()->streamDownload(function () use ($binary): void {
            echo $binary;
        }, $version->original_filename, [
            'Content-Type' => $version->mime ?: 'application/octet-stream',
        ]);
    }

    public function downloadVersion(ModuleRegistry $registry, Document $document, DocumentVersion $version, DocumentService $documents): StreamedResponse
    {
        abort_unless($registry->isEnabled('documents'), 404);
        Gate::authorize('download', $document);
        abort_unless($version->document_id === $document->id, 404);

        $binary = $documents->download($document, $version, request()->user());

        return response()->streamDownload(function () use ($binary): void {
            echo $binary;
        }, $version->original_filename, [
            'Content-Type' => $version->mime ?: 'application/octet-stream',
        ]);
    }

    public function restoreVersion(ModuleRegistry $registry, Document $document, DocumentVersion $version, DocumentService $documents)
    {
        abort_unless($registry->isEnabled('documents'), 404);
        Gate::authorize('manage', $document);
        abort_unless($version->document_id === $document->id, 404);

        $documents->restoreVersionAsNew($document, $version, request()->user());

        return redirect()
            ->route('documents.show', $document)
            ->with('status', "Restored v{$version->version_number} as a new current version.");
    }

    public function storage(Request $request): Response
    {
        abort_unless($request->hasValidSignature(), 403);
        $ref = base64_decode((string) $request->query('ref'), true);
        abort_unless(is_string($ref) && $ref !== '', 404);

        abort_unless(Storage::disk('local')->exists($ref), 404);

        return response(Storage::disk('local')->get($ref), 200, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }
}
