<?php

namespace App\Shared\Adapters\Drive;

use App\Shared\Contracts\DriveBroker;
use App\Shared\Models\DriveConnection;
use App\Shared\Services\CircuitBreaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Google Drive OAuth broker — admin connects a Google account with
 * read + update + write scopes. Cache-first resolve keeps core up when Drive is down.
 */
final class GoogleDriveOAuthDriver implements DriveBroker
{
    public const SCOPES = [
        'https://www.googleapis.com/auth/drive.file', // create/update app files
        'https://www.googleapis.com/auth/drive', // read/update existing Shared Drive files
        'https://www.googleapis.com/auth/userinfo.email',
    ];

    public function __construct(
        private readonly ?string $clientId,
        private readonly ?string $clientSecret,
        private readonly CircuitBreaker $breaker = new CircuitBreaker('drive'),
        private readonly string $disk = 'local',
        private readonly string $root = 'drive-cache',
    ) {}

    public function name(): string
    {
        return 'google_drive_oauth';
    }

    public function configured(): bool
    {
        return filled($this->clientId) && filled($this->clientSecret);
    }

    public function isConnected(): bool
    {
        $conn = $this->activeConnection();

        return $conn !== null && $conn->isActive();
    }

    public function authorizationUrl(string $state): ?string
    {
        if (! $this->configured()) {
            return null;
        }

        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => route('drive.oauth.callback'),
            'response_type' => 'code',
            'scope' => implode(' ', self::SCOPES),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.$query;
    }

    public function connect(string $code, int $userId): array
    {
        if (! $this->configured()) {
            throw new \RuntimeException('Google Drive OAuth is not configured (GOOGLE_DRIVE_CLIENT_ID/SECRET).');
        }

        return $this->breaker->call(function () use ($code, $userId) {
            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => route('drive.oauth.callback'),
                'grant_type' => 'authorization_code',
            ]);

            if (! $tokenResponse->successful()) {
                throw new \RuntimeException('Drive OAuth token exchange failed: '.$tokenResponse->body());
            }

            $tokens = $tokenResponse->json();
            $access = (string) ($tokens['access_token'] ?? '');
            $refresh = $tokens['refresh_token'] ?? null;
            $expiresIn = (int) ($tokens['expires_in'] ?? 3600);
            $scope = (string) ($tokens['scope'] ?? implode(' ', self::SCOPES));

            $email = $this->fetchAccountEmail($access);

            DriveConnection::query()->where('status', 'active')->update(['status' => 'revoked']);

            DriveConnection::query()->create([
                'connected_by' => $userId,
                'account_email' => $email,
                'access_token' => $access,
                'refresh_token' => $refresh,
                'expires_at' => now()->addSeconds($expiresIn - 60),
                'scopes' => $scope,
                'status' => 'active',
                'last_used_at' => now(),
            ]);

            $this->breaker->recordSync($this->name(), 'Drive connected as '.$email);

            return ['email' => $email, 'scopes' => $scope];
        }, $this->name());
    }

    public function disconnect(): void
    {
        $conn = $this->activeConnection();
        if ($conn) {
            try {
                if ($conn->access_token) {
                    Http::asForm()->post('https://oauth2.googleapis.com/revoke', [
                        'token' => $conn->access_token,
                    ]);
                }
            } catch (\Throwable) {
                // Best-effort revoke.
            }
            $conn->update(['status' => 'revoked', 'access_token' => '', 'refresh_token' => null]);
        }
    }

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

        if (! $this->isConnected()) {
            return [
                'ref' => $cacheRef,
                'cached' => false,
                'available' => false,
                'message' => 'Document temporarily unavailable (Drive not connected / cache miss).',
                'drive_file_id' => $driveFileId,
            ];
        }

        try {
            $bytes = $this->download($driveFileId);
            Storage::disk($this->disk)->put($cacheRef, $bytes);

            return [
                'ref' => $cacheRef,
                'cached' => false,
                'available' => true,
                'message' => null,
                'drive_file_id' => $driveFileId,
            ];
        } catch (\Throwable $e) {
            return [
                'ref' => $cacheRef,
                'cached' => false,
                'available' => false,
                'message' => 'Document temporarily unavailable (Drive/cache miss).',
                'drive_file_id' => $driveFileId,
            ];
        }
    }

    public function upload(string $name, string $contents, ?string $mime = null, ?string $folderId = null): array
    {
        $token = $this->accessToken();
        $mime ??= 'application/octet-stream';
        $metadata = ['name' => $name];
        if ($folderId) {
            $metadata['parents'] = [$folderId];
        }

        return $this->breaker->call(function () use ($token, $name, $contents, $mime, $metadata) {
            $boundary = 'intranet_'.Str::random(16);
            $body = "--{$boundary}\r\n"
                ."Content-Type: application/json; charset=UTF-8\r\n\r\n"
                .json_encode($metadata)."\r\n"
                ."--{$boundary}\r\n"
                ."Content-Type: {$mime}\r\n\r\n"
                .$contents."\r\n"
                ."--{$boundary}--";

            $response = Http::withToken($token)
                ->withHeaders([
                    'Content-Type' => 'multipart/related; boundary='.$boundary,
                ])
                ->withBody($body, 'multipart/related; boundary='.$boundary)
                ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,webViewLink');

            if (! $response->successful()) {
                throw new \RuntimeException('Drive upload failed: '.$response->body());
            }

            $json = $response->json();
            $this->touchConnection();

            return [
                'drive_file_id' => (string) ($json['id'] ?? ''),
                'name' => (string) ($json['name'] ?? $name),
                'web_view_link' => $json['webViewLink'] ?? null,
            ];
        }, $this->name());
    }

    public function update(string $driveFileId, string $contents, ?string $mime = null): array
    {
        $token = $this->accessToken();
        $mime ??= 'application/octet-stream';

        return $this->breaker->call(function () use ($token, $driveFileId, $contents, $mime) {
            $response = Http::withToken($token)
                ->withBody($contents, $mime)
                ->patch('https://www.googleapis.com/upload/drive/v3/files/'.urlencode($driveFileId).'?uploadType=media&fields=id,headRevisionId');

            if (! $response->successful()) {
                throw new \RuntimeException('Drive update failed: '.$response->body());
            }

            $json = $response->json();
            $this->touchConnection();

            return [
                'drive_file_id' => (string) ($json['id'] ?? $driveFileId),
                'revision_id' => $json['headRevisionId'] ?? null,
            ];
        }, $this->name());
    }

    public function download(string $driveFileId): string
    {
        $token = $this->accessToken();

        return $this->breaker->call(function () use ($token, $driveFileId) {
            $response = Http::withToken($token)
                ->get('https://www.googleapis.com/drive/v3/files/'.urlencode($driveFileId).'?alt=media');

            if (! $response->successful()) {
                throw new \RuntimeException('Drive download failed HTTP '.$response->status());
            }

            $this->touchConnection();

            return $response->body();
        }, $this->name());
    }

    public function health(): array
    {
        if (! $this->configured()) {
            return [
                'ok' => true,
                'driver' => $this->name(),
                'message' => 'Drive OAuth not configured — local document storage still works',
            ];
        }

        if (! $this->isConnected()) {
            return [
                'ok' => false,
                'driver' => $this->name(),
                'message' => 'Drive OAuth configured but no Google account connected',
            ];
        }

        $conn = $this->activeConnection();

        return [
            'ok' => true,
            'driver' => $this->name(),
            'message' => 'Connected as '.($conn?->account_email ?? 'unknown').' (read/update/write)',
        ];
    }

    public function putCache(string $checksum, string $contents): string
    {
        $ref = $this->root.'/'.$checksum;
        Storage::disk($this->disk)->put($ref, $contents);

        return $ref;
    }

    private function activeConnection(): ?DriveConnection
    {
        return DriveConnection::query()->where('status', 'active')->latest('id')->first();
    }

    private function accessToken(): string
    {
        $conn = $this->activeConnection();
        if (! $conn || ! $conn->isActive()) {
            throw new \RuntimeException('Google Drive is not connected. An admin must connect an account.');
        }

        if ($conn->isExpired()) {
            $this->refreshToken($conn);
            $conn->refresh();
        }

        return (string) $conn->access_token;
    }

    private function refreshToken(DriveConnection $conn): void
    {
        if (blank($conn->refresh_token)) {
            $conn->update(['status' => 'error']);
            throw new \RuntimeException('Drive refresh token missing — reconnect Google Drive.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $conn->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            $conn->update(['status' => 'error']);
            throw new \RuntimeException('Drive token refresh failed — reconnect Google Drive.');
        }

        $json = $response->json();
        $conn->update([
            'access_token' => (string) $json['access_token'],
            'expires_at' => now()->addSeconds(((int) ($json['expires_in'] ?? 3600)) - 60),
            'status' => 'active',
        ]);
    }

    private function fetchAccountEmail(string $accessToken): string
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.googleapis.com/oauth2/v2/userinfo');

        return (string) ($response->json('email') ?? 'unknown@drive');
    }

    private function touchConnection(): void
    {
        $this->activeConnection()?->update(['last_used_at' => now()]);
    }
}
