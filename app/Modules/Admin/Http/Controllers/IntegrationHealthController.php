<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Projects\Jobs\SyncProjectsJob;
use App\Modules\Projects\Services\ProjectSyncService;
use App\Shared\Contracts\DriveBroker;
use App\Shared\Models\DriveConnection;
use App\Shared\Services\IntegrationHealthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class IntegrationHealthController extends Controller
{
    public function index(IntegrationHealthService $health, DriveBroker $drive): View
    {
        return view('admin-module::integrations', [
            'rows' => $health->snapshot(),
            'drive' => $drive,
            'driveConfigured' => $drive->configured(),
            'driveEnabled' => (bool) config('integrations.drive.enabled'),
            'driveCallbackUrl' => route('drive.oauth.callback'),
            'driveFolderId' => config('integrations.drive.folder_id'),
            'driveConnection' => DriveConnection::query()->where('status', 'active')->latest('id')->first(),
        ]);
    }

    public function sync(ProjectSyncService $projects): RedirectResponse
    {
        $result = $projects->syncAll();

        $message = sprintf(
            'Synced Plane %d, Governex %d.',
            $result['plane'],
            $result['governex'],
        );

        if ($result['errors'] !== []) {
            return back()->with('warning', $message.' Errors: '.implode('; ', $result['errors']));
        }

        return back()->with('status', $message);
    }

    public function syncQueued(): RedirectResponse
    {
        SyncProjectsJob::dispatch();

        return back()->with('status', 'Project sync queued.');
    }
}
