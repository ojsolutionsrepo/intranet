<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate 5A — baseline browser hardening headers.
 */
final class SecureHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        if ($request->isSecure() || str_starts_with((string) config('app.url'), 'https://')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $scriptSrc = ["'self'", "'unsafe-inline'", "'unsafe-eval'"];
        $styleSrc = ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com'];
        $fontSrc = ["'self'", 'data:', 'https://fonts.gstatic.com'];
        $connectSrc = ["'self'"];

        // Vite HMR uses a different origin (port 5173). Without these, npm run
        // dev leaves the XAMPP app completely unstyled under CSP.
        if (app()->environment('local')) {
            $vite = [
                'http://localhost:5173',
                'http://127.0.0.1:5173',
                'http://[::1]:5173',
                'ws://localhost:5173',
                'ws://127.0.0.1:5173',
                'ws://[::1]:5173',
            ];
            $scriptSrc = array_merge($scriptSrc, $vite);
            $styleSrc = array_merge($styleSrc, $vite);
            $connectSrc = array_merge($connectSrc, $vite);
        }

        return implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', $scriptSrc),
            'style-src '.implode(' ', $styleSrc),
            'img-src \'self\' data: https:',
            'font-src '.implode(' ', $fontSrc),
            'connect-src '.implode(' ', $connectSrc),
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]);
    }
}
