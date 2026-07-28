<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Models\User;
use App\Shared\Services\AuditExporter;
use App\Shared\Services\AuditLogger;
use App\Shared\Services\SubjectAccessExporter;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplianceController extends Controller
{
    public function privacy(): View
    {
        return view('admin-module::privacy', [
            'contact' => config('gdpr.privacy_contact'),
            'retention' => config('gdpr.retention_days'),
        ]);
    }

    public function subjectAccessForm(): View
    {
        $users = User::query()->orderBy('name')->limit(200)->get(['id', 'name', 'email']);

        return view('admin-module::subject-access', compact('users'));
    }

    public function subjectAccessExport(Request $request, SubjectAccessExporter $exporter): StreamedResponse
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $subject = User::query()->findOrFail($data['user_id']);
        $path = $exporter->export($subject, $request->user());

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->download($path, basename($path));
    }

    public function auditExport(Request $request, AuditExporter $exporter, AuditLogger $audit): StreamedResponse
    {
        $filters = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'action' => 'nullable|string|max:120',
        ]);

        $audit->log('audit.exported', null, null, $filters);

        return $exporter->csv($filters);
    }
}
