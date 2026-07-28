<?php

namespace App\Shared\Adapters\Sso;

use App\Shared\Contracts\SsoAdapter;

/**
 * Break-glass local identity — always enabled. SSO is additive.
 */
final class LocalCredentialsDriver implements SsoAdapter
{
    public function name(): string
    {
        return 'local';
    }

    public function redirectUrl(string $state): string
    {
        return route('login', ['state' => $state]);
    }

    public function handleCallback(array $payload): array
    {
        throw new \RuntimeException('Local credentials use the standard login form, not an SSO callback.');
    }

    public function fetchUser(string $accessToken): ?array
    {
        return null;
    }

    public function revokeSession(string $token): void
    {
        // Sessions are revoked via Laravel Auth / UserAdminService.
    }

    public function health(): array
    {
        return [
            'ok' => true,
            'driver' => $this->name(),
            'message' => 'Local login always available',
        ];
    }
}
