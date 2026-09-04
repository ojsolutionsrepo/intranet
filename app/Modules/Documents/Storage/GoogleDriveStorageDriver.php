<?php

namespace App\Modules\Documents\Storage;

use App\Modules\Documents\Contracts\DocumentStorageAdapter;
use App\Shared\Contracts\DriveBroker;
use Illuminate\Support\Facades\Log;
use SplFileInfo;

/**
 * Writes to local storage always (cache / degrade path), and mirrors to Google Drive
 * when the broker is connected (UR-INT-02). Microsoft OneDrive is not supported.
 */
final class GoogleDriveStorageDriver implements DocumentStorageAdapter
{
    public function __construct(
        private readonly DriveBroker $drive,
        private readonly LocalStorageDriver $local = new LocalStorageDriver,
    ) {}

    public function put(string $path, string $contents, array $options = []): array
    {
        $stored = $this->local->put($path, $contents, $options);

        return $this->mirrorToDrive($stored, $contents, $options, basename($path));
    }

    public function putFile(string $path, SplFileInfo $file, array $options = []): array
    {
        $contents = (string) file_get_contents($file->getPathname());

        return $this->put($path, $contents, $options);
    }

    public function get(string $ref): string
    {
        return $this->local->get($ref);
    }

    public function signedUrl(string $ref, int $ttlSeconds = 300): string
    {
        return $this->local->signedUrl($ref, $ttlSeconds);
    }

    public function newVersion(string $path, string $contents, array $options = []): array
    {
        $localVersion = $this->local->newVersion($path, $contents, $options);

        return $this->mirrorToDrive($localVersion, $contents, $options, basename($localVersion['ref']));
    }

    public function listVersions(string $prefix): array
    {
        return $this->local->listVersions($prefix);
    }

    public function delete(string $ref): void
    {
        $this->local->delete($ref);
    }

    public function health(): array
    {
        $local = $this->local->health();
        $drive = $this->drive->health();

        return [
            'ok' => ($local['ok'] ?? false) && ($this->drive->isConnected() ? ($drive['ok'] ?? false) : true),
            'driver' => $this->drive->isConnected() ? 'google_drive+local' : 'local',
            'message' => $this->drive->isConnected()
                ? 'Drive connected; uploads mirror to Google Drive with local cache'
                : 'Drive not connected; using local document storage only',
        ];
    }

    /**
     * @param  array<string, mixed>  $stored
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function mirrorToDrive(array $stored, string $contents, array $options, string $fallbackName): array
    {
        if (! $this->drive->isConnected()) {
            return $stored;
        }

        try {
            $folderId = config('integrations.drive.folder_id');
            $folderId = is_string($folderId) && $folderId !== '' ? $folderId : null;
            $name = is_string($options['name'] ?? null) && $options['name'] !== ''
                ? $options['name']
                : $fallbackName;

            $uploaded = $this->drive->upload(
                $name,
                $contents,
                $options['mime'] ?? null,
                $folderId,
            );

            $stored['drive_file_id'] = $uploaded['drive_file_id'] ?? null;
            $stored['disk'] = 'drive';
            $stored['drive_mirrored'] = filled($stored['drive_file_id']);
        } catch (\Throwable $e) {
            Log::warning('Document Drive mirror failed', [
                'message' => $e->getMessage(),
            ]);
            $stored['drive_mirrored'] = false;
            $stored['drive_error'] = $e->getMessage();
        }

        return $stored;
    }
}
