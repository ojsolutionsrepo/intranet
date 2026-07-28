<?php

namespace App\Shared\Adapters\Sso;

use App\Shared\Contracts\SsoAdapter;
use App\Shared\Services\CircuitBreaker;

/**
 * Google Workspace OIDC stub — inactive until client credentials are configured.
 * Site remains usable via LocalCredentialsDriver.
 */
final class GoogleOidcDriver implements SsoAdapter
{
    public function __construct(
        private readonly ?string $clientId,
        private readonly ?string $clientSecret,
        private readonly CircuitBreaker $breaker = new CircuitBreaker('sso'),
    ) {}

    public function name(): string
    {
        return 'google_oidc';
    }

    public function configured(): bool
    {
        return filled($this->clientId) && filled($this->clientSecret);
    }

    public function redirectUrl(string $state): string
    {
        if (! $this->configured()) {
            return route('login');
        }

        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => route('sso.callback', ['provider' => 'google']),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'hd' => config('integrations.sso.hosted_domain'),
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.$query;
    }

    public function handleCallback(array $payload): array
    {
        return $this->breaker->call(function () {
            if (! $this->configured()) {
                throw new \RuntimeException('Google OIDC is not configured.');
            }

            // Real token exchange deferred until Workspace tenant is provisioned.
            throw new \RuntimeException('Google OIDC callback not connected yet — use local login or JWT SSO.');
        }, $this->name());
    }

    public function fetchUser(string $accessToken): ?array
    {
        return null;
    }

    public function revokeSession(string $token): void {}

    public function health(): array
    {
        return [
            'ok' => $this->configured(),
            'driver' => $this->name(),
            'message' => $this->configured()
                ? 'OIDC client configured (awaiting Workspace tenant)'
                : 'GOOGLE_OIDC_CLIENT_ID/SECRET not set — local login unaffected',
        ];
    }
}
