<?php

namespace App\Modules\Admin\Livewire;

use App\Models\User;
use App\Shared\Contracts\DriveBroker;
use App\Shared\Services\AuditLogger;
use App\Shared\Services\Installer;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DriveCredentialsForm extends Component
{
    public string $client_id = '';

    public string $client_secret = '';

    public string $folder_id = '';

    public bool $enabled = false;

    public string $callbackUrl = '';

    public function mount(): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->can('admin.integrations.view'), 403);

        $this->client_id = (string) config('integrations.drive.client_id', '');
        $this->client_secret = (string) config('integrations.drive.client_secret', '');
        $this->folder_id = (string) config('integrations.drive.folder_id', '');
        $this->enabled = (bool) config('integrations.drive.enabled', false);
        $this->callbackUrl = route('drive.oauth.callback');
    }

    public function save(Installer $installer, AuditLogger $audit): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->can('admin.integrations.view'), 403);

        $this->validate([
            'client_id' => ['nullable', 'string', 'max:255'],
            'client_secret' => ['nullable', 'string', 'max:255'],
            'folder_id' => ['nullable', 'string', 'max:255'],
            'enabled' => ['boolean'],
        ]);

        $clientId = trim($this->client_id);
        $clientSecret = trim($this->client_secret);
        $folderId = trim($this->folder_id);

        if ($this->enabled && ($clientId === '' || $clientSecret === '')) {
            $this->addError('enabled', 'Client ID and Secret are required when Drive broker is enabled.');

            return;
        }

        $before = [
            'DRIVE_BROKER_ENABLED' => (bool) config('integrations.drive.enabled'),
            'GOOGLE_DRIVE_CLIENT_ID' => filled(config('integrations.drive.client_id')),
            'GOOGLE_DRIVE_CLIENT_SECRET' => filled(config('integrations.drive.client_secret')),
            'GOOGLE_DRIVE_FOLDER_ID' => filled(config('integrations.drive.folder_id')),
        ];

        try {
            $installer->writeEnv([
                'DRIVE_BROKER_ENABLED' => $this->enabled ? 'true' : 'false',
                'GOOGLE_DRIVE_CLIENT_ID' => $clientId,
                'GOOGLE_DRIVE_CLIENT_SECRET' => $clientSecret,
                'GOOGLE_DRIVE_FOLDER_ID' => $folderId,
            ]);
        } catch (\Throwable $e) {
            $this->addError('client_id', 'Could not write .env: '.$e->getMessage());

            return;
        }

        config([
            'integrations.drive.enabled' => $this->enabled,
            'integrations.drive.client_id' => $clientId !== '' ? $clientId : null,
            'integrations.drive.client_secret' => $clientSecret !== '' ? $clientSecret : null,
            'integrations.drive.folder_id' => $folderId !== '' ? $folderId : null,
        ]);

        app()->forgetInstance(DriveBroker::class);

        $audit->log('drive.credentials_updated', null, $before, [
            'DRIVE_BROKER_ENABLED' => $this->enabled,
            'GOOGLE_DRIVE_CLIENT_ID' => $clientId !== '',
            'GOOGLE_DRIVE_CLIENT_SECRET' => $clientSecret !== '',
            'GOOGLE_DRIVE_FOLDER_ID' => $folderId !== '',
        ]);

        session()->flash(
            'status',
            $this->enabled && $clientId !== '' && $clientSecret !== ''
                ? 'Drive credentials saved. You can connect Google Drive now.'
                : 'Drive credentials saved to .env.'
        );

        // Full page load so Integration health re-reads .env-backed config.
        $this->redirect(route('admin.integrations'), navigate: false);
    }

    public function render()
    {
        return view('admin-module::livewire.drive-credentials');
    }
}
