<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Shared\Adapters\Sso\JwtSsoDriver;
use App\Shared\Contracts\SsoAdapter;
use App\Shared\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Throwable;

class SsoCallbackController extends Controller
{
    public function __invoke(Request $request, string $provider, SsoAdapter $sso, AuditLogger $audit): RedirectResponse
    {
        // Local credentials never go through this callback.
        if ($provider === 'local') {
            return redirect()->route('login');
        }

        try {
            $driver = match ($provider) {
                'jwt' => app(JwtSsoDriver::class),
                default => $sso,
            };

            $identity = $driver->handleCallback($request->all());
            $user = User::query()->where('email', $identity['email'])->first();

            if (! $user || ! $user->is_active) {
                return redirect()->route('login')
                    ->withErrors(['email' => 'No active intranet account for this SSO identity. Use local login or ask an admin.']);
            }

            Auth::login($user, true);
            $request->session()->regenerate();
            $audit->log('sso.login', $user, null, ['provider' => $provider, 'driver' => $driver->name()]);

            return redirect()->intended(route('dashboard'));
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')
                ->withErrors(['email' => 'SSO failed: '.$e->getMessage().' You can still sign in with email and password.']);
        }
    }
}
