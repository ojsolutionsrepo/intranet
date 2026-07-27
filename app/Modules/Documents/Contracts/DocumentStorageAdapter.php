<?php

namespace App\Modules\Documents\Contracts;

use SplFileInfo;

interface DocumentStorageAdapter
{
    /**
     * @return array{ref: string, disk: string, size: int, mime: string|null}
     */
    public function put(string $path, string $contents, array $options = []): array;

    public function get(string $ref): string;

    public function signedUrl(string $ref, int $ttlSeconds = 300): string;

    /**
     * @return array{ref: string, disk: string, size: int, mime: string|null}
     */
    public function newVersion(string $path, string $contents, array $options = []): array;

    /**
     * @return list<array{ref: string, modified: string|null}>
     */
    public function listVersions(string $prefix): array;

    public function delete(string $ref): void;

    /**
     * @return array{ok: bool, driver: string, message: string}
     */
    public function health(): array;

    public function putFile(string $path, SplFileInfo $file, array $options = []): array;
}
