<?php

namespace App\Modules\Documents\Storage;

use App\Modules\Documents\Contracts\DocumentStorageAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use SplFileInfo;

final class LocalStorageDriver implements DocumentStorageAdapter
{
    public function __construct(
        private readonly string $disk = 'local',
        private readonly string $root = 'documents',
    ) {}

    public function put(string $path, string $contents, array $options = []): array
    {
        $ref = $this->root.'/'.ltrim($path, '/');
        Storage::disk($this->disk)->put($ref, $contents);

        return [
            'ref' => $ref,
            'disk' => $this->disk,
            'size' => strlen($contents),
            'mime' => $options['mime'] ?? null,
        ];
    }

    public function putFile(string $path, SplFileInfo $file, array $options = []): array
    {
        $contents = (string) file_get_contents($file->getPathname());

        return $this->put($path, $contents, $options);
    }

    public function get(string $ref): string
    {
        return Storage::disk($this->disk)->get($ref);
    }

    public function signedUrl(string $ref, int $ttlSeconds = 300): string
    {
        // Local driver: temporary signed app route token style URL.
        return URL::temporarySignedRoute(
            'documents.storage',
            now()->addSeconds($ttlSeconds),
            ['ref' => base64_encode($ref)],
        );
    }

    public function newVersion(string $path, string $contents, array $options = []): array
    {
        $versioned = pathinfo($path, PATHINFO_DIRNAME).'/'.pathinfo($path, PATHINFO_FILENAME)
            .'_v'.Str::lower(Str::random(6)).'.'.pathinfo($path, PATHINFO_EXTENSION);

        return $this->put($versioned, $contents, $options);
    }

    public function listVersions(string $prefix): array
    {
        $files = Storage::disk($this->disk)->files($this->root.'/'.ltrim($prefix, '/'));

        return collect($files)->map(fn (string $ref) => [
            'ref' => $ref,
            'modified' => optional(Storage::disk($this->disk)->lastModified($ref), fn ($t) => date('c', $t)),
        ])->values()->all();
    }

    public function delete(string $ref): void
    {
        Storage::disk($this->disk)->delete($ref);
    }

    public function health(): array
    {
        try {
            $probe = $this->root.'/.health';
            Storage::disk($this->disk)->put($probe, 'ok');
            Storage::disk($this->disk)->delete($probe);

            return ['ok' => true, 'driver' => 'local', 'message' => 'Local document storage writable'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'driver' => 'local', 'message' => $e->getMessage()];
        }
    }
}
