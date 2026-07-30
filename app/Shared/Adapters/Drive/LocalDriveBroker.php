<?php

namespace App\Shared\Adapters\Drive;

use App\Shared\Contracts\DriveBroker;
use App\Shared\Services\CircuitBreaker;
use Illuminate\Support\Facades\Storage;

/**
 * Local/S3 cache-first Drive broker used when OAuth is not configured.
 * Write/update/download require a live Google connection — surface clear errors.
 */
final class LocalDriveBroker implements DriveBroker
{
    public function __construct(
        private readonly CircuitBreaker $breaker = new CircuitBreaker('drive'),
        private readonly string $disk = 'local',
        private readonly string $root = 'drive-cache',
    ) {}

    public function name(): string
    {
        return 'local_mirror';
    }

    public function configured(): bool
    {
        return false;
    }

    public function isConnected(): bool
    {
        return false;
    }

    public function authorizationUrl(string $state): ?string
    {
        return null;
    }

    public function connect(string $code, int $userId): array
    {
        throw new \RuntimeException('Configure GOOGLE_DRIVE_CLIENT_ID and GOOGLE_DRIVE_CLIENT_SECRET to connect Drive.');
    }

    public function disconnect(): void {}

    public function resolve(string $driveFileId, string $revisionId, string $checksum): array
    {
        $cacheRef = $this->root.'/'.$checksum;

        if (Storage::disk($this->disk)->exists($cacheRef)) {
            return [
                'ref' => $cacheRef,
                'cached' => true,
                'available' => true,
                'message' => null,
                'drive_file_id' => $driveFileId,
            ];
        }

        return [
            'ref' => $cacheRef,
            'cached' => false,
            'available' => false,
            'message' => 'Document temporarily unavailable (Drive/cache miss).',
            'drive_file_id' => $driveFileId,
        ];
    }

    public function upload(string $name, string $contents, ?string $mime = null, ?string $folderId = null): array
    {
        throw new \RuntimeException('Drive write requires a connected Google account.');
    }

    public function update(string $driveFileId, string $contents, ?string $mime = null): array
    {
        throw new \RuntimeException('Drive update requires a connected Google account.');
    }

    public function download(string $driveFileId): string
    {
        throw new \RuntimeException('Drive read requires a connected Google account or a local cache hit.');
    }

    public function putCache(string $checksum, string $contents): string
    {
        $ref = $this->root.'/'.$checksum;
        Storage::disk($this->disk)->put($ref, $contents);
        $this->breaker->recordSync($this->name(), 'Cache write OK');

        return $ref;
    }

    public function health(): array
    {
        $writable = Storage::disk($this->disk)->put($this->root.'/.health', 'ok');
        if ($writable) {
            Storage::disk($this->disk)->delete($this->root.'/.health');
        }

        return [
            'ok' => (bool) $writable,
            'driver' => $this->name(),
            'message' => $writable
                ? 'Cache-only mode — set Google Drive OAuth credentials to enable read/update/write'
                : 'Drive cache not writable',
        ];
    }
}
