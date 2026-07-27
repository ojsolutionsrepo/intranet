<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Shared\Services\Installer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class InstallController extends Controller
{
    public function __construct(private readonly Installer $installer) {}

    public function requirements(): View
    {
        return view('install.requirements', [
            'checks' => $this->installer->requirements(),
            'passed' => $this->installer->requirementsPassed(),
            'step' => 1,
        ]);
    }

    public function storeRequirements(): RedirectResponse
    {
        if (! $this->installer->requirementsPassed()) {
            return back()->withErrors(['requirements' => 'Resolve the failed checks before continuing.']);
        }

        session(['install.requirements_ok' => true]);

        return redirect()->route('install.database');
    }

    public function database(): View|RedirectResponse
    {
        if (! session('install.requirements_ok')) {
            return redirect()->route('install.requirements');
        }

        return view('install.database', [
            'step' => 2,
            'hasSqlite' => extension_loaded('pdo_sqlite'),
            'hasMysql' => extension_loaded('pdo_mysql'),
        ]);
    }

    public function storeDatabase(Request $request): RedirectResponse
    {
        if (! session('install.requirements_ok')) {
            return redirect()->route('install.requirements');
        }

        $data = $request->validate([
            'connection' => ['required', 'in:sqlite,mysql'],
            'host' => ['nullable', 'required_if:connection,mysql', 'string', 'max:255'],
            'port' => ['nullable', 'required_if:connection,mysql', 'string', 'max:10'],
            'database' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'required_if:connection,mysql', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['connection'] === 'sqlite') {
            $data['database'] = database_path('database.sqlite');
        } else {
            $data['database'] = $data['database'] ?: 'oj_intranet';
            $data['host'] = $data['host'] ?: '127.0.0.1';
            $data['port'] = $data['port'] ?: '3306';
            $data['username'] = $data['username'] ?: 'root';
            $data['password'] = $data['password'] ?? '';
        }

        try {
            $this->installer->testDatabaseConnection($data);
            $this->installer->ensureAppKey();
            $this->installer->setAppUrlFromRequest($request->root());
            $this->installer->applyDatabaseConfig($data);
            $this->installer->migrate();
        } catch (Throwable $e) {
            return back()->withInput()->withErrors([
                'database' => 'Database setup failed: '.$this->installer->failureMessage($e),
            ]);
        }

        session([
            'install.database_ok' => true,
            'install.connection' => $data['connection'],
        ]);

        return redirect()->route('install.admin');
    }

    public function admin(): View|RedirectResponse
    {
        if (! session('install.database_ok')) {
            return redirect()->route('install.database');
        }

        return view('install.admin', ['step' => 3]);
    }

    public function storeAdmin(Request $request): RedirectResponse
    {
        if (! session('install.database_ok')) {
            return redirect()->route('install.database');
        }

        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $this->installer->seedFoundation($data);
            $this->installer->markInstalled();
        } catch (Throwable $e) {
            return back()->withInput()->withErrors([
                'admin' => 'Could not create admin: '.$this->installer->failureMessage($e),
            ]);
        }

        $request->session()->forget([
            'install.requirements_ok',
            'install.database_ok',
            'install.connection',
        ]);

        return redirect()->route('install.complete');
    }

    public function complete(): View|RedirectResponse
    {
        if (! Installer::isInstalled()) {
            return redirect()->route('install.requirements');
        }

        return view('install.complete', ['step' => 4]);
    }
}
