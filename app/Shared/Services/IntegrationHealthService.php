<?php

namespace App\Shared\Services;

use App\Shared\Adapters\Sso\GoogleOidcDriver;
use App\Shared\Adapters\Sso\JwtSsoDriver;
use App\Shared\Adapters\Sso\LocalCredentialsDriver;
use App\Shared\Contracts\DriveBroker;
use App\Shared\Contracts\GovernexAdapter;
use App\Shared\Contracts\PlaneAdapter;
use App\Shared\Contracts\SsoAdapter;
use App\Shared\Models\IntegrationHealth;
use Throwable;

final class IntegrationHealthService
{
    public function __construct(
        private readonly SsoAdapter $sso,
        private readonly DriveBroker $drive,
        private readonly PlaneAdapter $plane,
        private readonly GovernexAdapter $governex,
        private readonly LocalCredentialsDriver $localSso,
        private readonly JwtSsoDriver $jwtSso,
        private readonly GoogleOidcDriver $googleSso,
    ) {}

    /**
     * @return list<array{name: string, driver: string, ok: bool, message: string, status: string, circuit: string, last_sync_at: ?string, last_success_at: ?string}>
     */
    public function snapshot(): array
    {
        $checks = [
            'sso_local' => ['probe' => fn () => $this->localSso->health(), 'store' => null],
            'sso_active' => ['probe' => fn () => $this->sso->health(), 'store' => 'sso'],
            'sso_jwt' => ['probe' => fn () => $this->jwtSso->health(), 'store' => null],
            'sso_google' => ['probe' => fn () => $this->googleSso->health(), 'store' => null],
            'drive' => ['probe' => fn () => $this->drive->health(), 'store' => 'drive'],
            'plane' => ['probe' => fn () => $this->plane->health(), 'store' => 'plane'],
            'governex' => ['probe' => fn () => $this->governex->health(), 'store' => 'governex'],
        ];

        $rows = [];
        foreach ($checks as $name => $meta) {
            try {
                $health = ($meta['probe'])();
            } catch (Throwable $e) {
                $health = ['ok' => false, 'driver' => $name, 'message' => $e->getMessage()];
            }

            $stored = $meta['store']
                ? IntegrationHealth::query()->where('name', $meta['store'])->first()
                : null;

            $rows[] = [
                'name' => $name,
                'driver' => (string) ($health['driver'] ?? $name),
                'ok' => (bool) ($health['ok'] ?? false),
                'message' => (string) ($health['message'] ?? ''),
                'status' => $stored?->status ?? (($health['ok'] ?? false) ? 'ok' : 'degraded'),
                'circuit' => $stored?->circuit ?? 'closed',
                'last_sync_at' => $stored?->last_sync_at?->toDateTimeString(),
                'last_success_at' => $stored?->last_success_at?->toDateTimeString(),
            ];
        }

        return $rows;
    }
}
