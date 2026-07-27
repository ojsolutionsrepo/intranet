<?php

namespace App\Http\Middleware;

use App\Shared\Services\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        if (isset($user->is_active) && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been deactivated.']);
        }

        $timeoutMinutes = (int) app(Settings::class)->get('session_idle_timeout', 480);
        $lastActivity = $request->session()->get('last_activity_at');

        if ($lastActivity && now()->diffInMinutes($lastActivity) >= $timeoutMinutes) {
            $destination = $request->fullUrl();
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login', ['redirect' => $destination])
                ->withErrors(['email' => 'Your session expired due to inactivity.']);
        }

        $request->session()->put('last_activity_at', now());

        return $next($request);
    }
}
