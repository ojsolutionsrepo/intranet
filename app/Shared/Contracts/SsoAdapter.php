<?php

namespace App\Shared\Contracts;

/**
 * Identity / dashboard SSO. Local credentials always remain available.
 */
interface SsoAdapter
{
    public function name(): string;

    public function redirectUrl(string $state): string;

    /**
     * @return array{email: string, name: string|null, external_id: string|null}
     */
    public function handleCallback(array $payload): array;

    /**
     * @return array{email: string, name: string|null}|null
     */
    public function fetchUser(string $accessToken): ?array;

    public function revokeSession(string $token): void;

    /**
     * @return array{ok: bool, driver: string, message: string}
     */
    public function health(): array;
}
