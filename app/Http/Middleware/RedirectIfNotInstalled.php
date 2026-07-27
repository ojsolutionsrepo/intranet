<?php

namespace App\Http\Middleware;

use App\Shared\Services\Installer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Installer::isInstalled() && ! $request->is('install', 'install/*', 'up')) {
            return redirect()->route('install.requirements');
        }

        return $next($request);
    }
}
