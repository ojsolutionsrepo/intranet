<?php

namespace App\Http\Controllers\Integrations;

use App\Shared\Contracts\DriveBroker;
use App\Shared\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Throwable;

class DriveOAuthController extends Controller
{
    public function redirect(Request $request, DriveBroker $drive): RedirectResponse
    {
        abort_unless($request->user()?->can('admin.integrations.view'), 403);

        $state = Str::random(40);
        session(['drive_oauth_state' => $state]);

        $url = $drive->authorizationUrl($state);
        if (! $url) {
            return redirect()->route('admin.integrations')
                ->with('warning', 'Set GOOGLE_DRIVE_CLIENT_ID and GOOGLE_DRIVE_CLIENT_SECRET in .env first.');
        }

        return redirect()->away($url);
    }

    public function callback(Request $request, DriveBroker $drive, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user() !== null, 403);

        if ($request->string('state')->toString() !== session('drive_oauth_state')) {
            return redirect()->route('admin.integrations')
                ->with('warning', 'Drive OAuth state mismatch — try connecting again.');
        }

        session()->forget('drive_oauth_state');

        if ($request->filled('error')) {
            return redirect()->route('admin.integrations')
                ->with('warning', 'Drive connect cancelled: '.$request->string('error'));
        }

        try {
            $result = $drive->connect($request->string('code')->toString(), (int) $request->user()->id);
            $audit->log('drive.connected', null, null, [
                'email' => $result['email'],
                'scopes' => $result['scopes'],
            ]);

            return redirect()->route('admin.integrations')
                ->with('status', 'Google Drive connected as '.$result['email'].' (read / update / write).');
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('admin.integrations')
                ->with('warning', 'Drive connect failed: '.$e->getMessage());
        }
    }

    public function disconnect(Request $request, DriveBroker $drive, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()?->can('admin.integrations.view'), 403);
        $drive->disconnect();
        $audit->log('drive.disconnected');

        return redirect()->route('admin.integrations')
            ->with('status', 'Google Drive disconnected. Local document storage still works.');
    }
}
