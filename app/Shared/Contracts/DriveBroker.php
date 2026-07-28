<?php

namespace App\Shared\Contracts;

/**
 * Google Drive broker — OAuth-connected account with read / update / write.
 * Listings never hit Drive for document library ACL; broker is for file I/O.
 */
interface DriveBroker
{
    public function name(): string;

    public function isConnected(): bool;

    /**
     * OAuth authorization URL for connecting a Google account (admin).
     */
    public function authorizationUrl(string $state): ?string;

    /**
     * Exchange OAuth code and persist tokens.
     *
     * @return array{email: string, scopes: string}
     */
    public function connect(string $code, int $userId): array;

    public function disconnect(): void;

    /**
     * Fetch a revision into local/S3 cache when missing.
     *
     * @return array{ref: string, cached: bool, available: bool, message: string|null, drive_file_id: string|null}
     */
    public function resolve(string $driveFileId, string $revisionId, string $checksum): array;

    /**
     * Upload new file (write).
     *
     * @return array{drive_file_id: string, name: string, web_view_link: string|null}
     */
    public function upload(string $name, string $contents, ?string $mime = null, ?string $folderId = null): array;

    /**
     * Update existing file contents (update).
     *
     * @return array{drive_file_id: string, revision_id: string|null}
     */
    public function update(string $driveFileId, string $contents, ?string $mime = null): array;

    /**
     * Download file bytes (read).
     */
    public function download(string $driveFileId): string;

    public function health(): array;
}
