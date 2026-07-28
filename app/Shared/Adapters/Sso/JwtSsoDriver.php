<?php

namespace App\Shared\Adapters\Sso;

use App\Shared\Contracts\SsoAdapter;
use App\Shared\Services\CircuitBreaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Signed JWT handshake (60s TTL + jti) for dashboard SSO when OIDC is not configured.
 */
final class JwtSsoDriver implements SsoAdapter
{
    public function __construct(
        private readonly string $sharedSecret,
        private readonly CircuitBreaker $breaker = new CircuitBreaker('sso'),
    ) {}

    public function name(): string
    {
        return 'jwt';
    }

    public function redirectUrl(string $state): string
    {
        // External dashboard would redirect here with a signed token query param.
        return route('sso.callback', ['provider' => 'jwt', 'state' => $state]);
    }

    public function handleCallback(array $payload): array
    {
        return $this->breaker->call(function () use ($payload) {
            $token = (string) ($payload['token'] ?? '');
            if ($token === '') {
                throw new \InvalidArgumentException('Missing SSO token.');
            }

            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                throw new \InvalidArgumentException('Malformed JWT.');
            }

            [$h64, $p64, $s64] = $parts;
            $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', $h64.'.'.$p64, $this->sharedSecret, true)), '+/', '-_'), '=');
            if (! hash_equals($expected, $s64)) {
                throw new \RuntimeException('Invalid SSO signature.');
            }

            $claims = json_decode($this->b64($p64), true);
            if (! is_array($claims)) {
                throw new \RuntimeException('Invalid SSO payload.');
            }

            $exp = (int) ($claims['exp'] ?? 0);
            if ($exp < time()) {
                throw new \RuntimeException('SSO token expired.');
            }

            $jti = (string) ($claims['jti'] ?? '');
            if ($jti === '') {
                throw new \RuntimeException('SSO token missing jti.');
            }

            if (DB::table('sso_jtis')->where('jti', $jti)->exists()) {
                throw new \RuntimeException('SSO token already used.');
            }

            DB::table('sso_jtis')->insert([
                'jti' => $jti,
                'expires_at' => now()->addMinutes(5),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $email = strtolower((string) ($claims['email'] ?? ''));
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('SSO token missing email.');
            }

            return [
                'email' => $email,
                'name' => $claims['name'] ?? null,
                'external_id' => $claims['sub'] ?? null,
            ];
        }, $this->name());
    }

    public function fetchUser(string $accessToken): ?array
    {
        try {
            return $this->handleCallback(['token' => $accessToken]);
        } catch (\Throwable) {
            return null;
        }
    }

    public function revokeSession(string $token): void
    {
        // JWT is one-shot via jti table.
    }

    public function health(): array
    {
        $configured = $this->sharedSecret !== '';

        return [
            'ok' => $configured,
            'driver' => $this->name(),
            'message' => $configured
                ? 'JWT SSO ready (60s TTL + jti)'
                : 'SSO_JWT_SECRET not set — local login only',
        ];
    }

    /**
     * Issue a short-lived token for tests / dashboard handshake demos.
     */
    public function issueToken(string $email, string $name, int $ttlSeconds = 60): string
    {
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode([
            'email' => $email,
            'name' => $name,
            'sub' => 'jwt:'.$email,
            'jti' => (string) Str::uuid(),
            'exp' => time() + $ttlSeconds,
            'iat' => time(),
        ])), '+/', '-_'), '=');

        $sig = rtrim(strtr(base64_encode(hash_hmac('sha256', $header.'.'.$payload, $this->sharedSecret, true)), '+/', '-_'), '=');

        return $header.'.'.$payload.'.'.$sig;
    }

    private function b64(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }
}
