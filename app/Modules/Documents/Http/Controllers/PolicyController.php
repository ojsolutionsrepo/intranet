<?php

namespace App\Modules\Documents\Http\Controllers;

use App\Core\Modules\ModuleRegistry;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Services\PolicyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PolicyController extends Controller
{
    public function index(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('documents'), 404);

        return view('documents::policies');
    }

    public function acknowledge(ModuleRegistry $registry, Document $document, PolicyService $policies, Request $request): RedirectResponse
    {
        abort_unless($registry->isEnabled('documents'), 404);
        $policies->acknowledge($document, $request->user());

        return back()->with('status', 'Policy acknowledged for the current version.');
    }

    public function exportCompliance(ModuleRegistry $registry, Document $document, PolicyService $policies): StreamedResponse
    {
        abort_unless($registry->isEnabled('documents'), 404);
        abort_unless($document->is_policy, 404);

        $rows = $policies->complianceMatrix($document);

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['user', 'email', 'status', 'acknowledged_at']);
            foreach ($rows as $row) {
                fputcsv($out, [$row['user'], $row['email'], $row['status'], $row['acknowledged_at']]);
            }
            fclose($out);
        }, 'policy-'.$document->id.'-compliance.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
