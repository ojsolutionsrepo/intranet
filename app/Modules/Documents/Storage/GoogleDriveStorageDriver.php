<?php

namespace App\Modules\Documents\Storage;

use App\Modules\Documents\Contracts\DocumentStorageAdapter;
use App\Shared\Contracts\DriveBroker;
use SplFileInfo;

/**
 * Writes to local storage always (cache / degrade path), and mirrors to Google Drive
 * when the broker is connected (UR-INT-02).
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

        if (! $this->drive->isConnected()) {
            return $stored;
        }

        try {
            $folderId = config('integrations.drive.folder_id');
            $folderId = is_string($folderId) && $folderId !== '' ? $folderId : null;
            $name = basename($path);
            $uploaded = $this->drive->upload(
                $name,
                $contents,
                $options['mime'] ?? null,
                $folderId,
            );

            $stored['drive_file_id'] = $uploaded['drive_file_id'] ?? null;
            $stored['disk'] = 'drive';
        } catch (\Throwable) {
            // Degrade, never fail — local copy remains authoritative for download.
        }

        return $stored;
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
        // Prefer a distinct local path, then mirror to Drive like put().
        $localVersion = $this->local->newVersion($path, $contents, $options);

        if (! $this->drive->isConnected()) {
            return $localVersion;
        }

        try {
            $folderId = config('integrations.drive.folder_id');
            $folderId = is_string($folderId) && $folderId !== '' ? $folderId : null;
            $uploaded = $this->drive->upload(
                basename($localVersion['ref']),
                $contents,
                $options['mime'] ?? null,
                $folderId,
            );
            $localVersion['drive_file_id'] = $uploaded['drive_file_id'] ?? null;
            $localVersion['disk'] = 'drive';
        } catch (\Throwable) {
            // Keep local version.
        }

        return $localVersion;
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
}
