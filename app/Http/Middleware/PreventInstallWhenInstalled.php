<?php

namespace App\Http\Middleware;

use App\Shared\Services\Installer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventInstallWhenInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Installer::isInstalled()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
