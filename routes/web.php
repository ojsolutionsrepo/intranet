<?php

use App\Http\Controllers\Auth\SsoCallbackController;
use App\Http\Controllers\Install\InstallController;
use App\Http\Controllers\Integrations\DriveOAuthController;
use App\Http\Middleware\EnsureSessionIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Shared\Services\Settings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::middleware('install.guest')->prefix('install')->group(function () {
    Route::get('/', [InstallController::class, 'requirements'])->name('install.requirements');
    Route::post('/requirements', [InstallController::class, 'storeRequirements'])->name('install.requirements.store');
    Route::get('/database', [InstallController::class, 'database'])->name('install.database');
    Route::post('/database', [InstallController::class, 'storeDatabase'])->name('install.database.store');
    Route::get('/admin', [InstallController::class, 'admin'])->name('install.admin');
    Route::post('/admin', [InstallController::class, 'storeAdmin'])->name('install.admin.store');
});

Route::get('/install/complete', [InstallController::class, 'complete'])->name('install.complete');

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/sso/callback/{provider}', SsoCallbackController::class)
    ->name('sso.callback')
    ->middleware('web');

Route::middleware(['web', 'auth', 'session.active'])->group(function () {
    Route::get('/integrations/drive/connect', [DriveOAuthController::class, 'redirect'])
        ->name('drive.oauth.redirect');
    Route::get('/integrations/drive/callback', [DriveOAuthController::class, 'callback'])
        ->name('drive.oauth.callback');
    Route::post('/integrations/drive/disconnect', [DriveOAuthController::class, 'disconnect'])
        ->name('drive.oauth.disconnect');
});

Route::middleware(['auth', EnsureSessionIsActive::class])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::middleware([EnsureUserIsAdmin::class])->prefix('admin')->group(function () {
        Route::get('/', function () {
            $modules = DB::table('modules')->orderBy('name')->get();
            $idleTimeout = app(Settings::class)->get('session_idle_timeout', 480);

            return view('admin.index', [
                'modules' => $modules,
                'idleTimeout' => $idleTimeout,
            ]);
        })->name('admin.index');
    });
});
