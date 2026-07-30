@extends('layouts.app')

@section('title', 'Administration')

@section('breadcrumb')
    <span class="text-[var(--ink-900)] font-medium">Administration</span>
@endsection

@section('content')
    <h1 class="font-display font-bold text-3xl tracking-tight mb-2">Administration</h1>
    <p class="note mb-6">Admin-only area. Staff receive 403 on this route (UR-AUT-07 / Gate 0.13).</p>

    <div class="grid gap-4 md:grid-cols-3 mb-6">
        <a href="{{ route('admin.users.index') }}" class="card p-5 hover:border-[var(--sig-500)] block">
            <h3 class="font-display font-semibold text-base mb-1">Users</h3>
            <p class="note">Create, edit, and deactivate accounts.</p>
        </a>
        <a href="{{ route('admin.departments') }}" class="card p-5 hover:border-[var(--sig-500)] block">
            <h3 class="font-display font-semibold text-base mb-1">Departments</h3>
            <p class="note">Add and edit departments and leads.</p>
        </a>
        <a href="{{ route('admin.permissions') }}" class="card p-5 hover:border-[var(--sig-500)] block">
            <h3 class="font-display font-semibold text-base mb-1">Permissions</h3>
            <p class="note">Role × permission matrix.</p>
        </a>
        <a href="{{ route('directory.import') }}" class="card p-5 hover:border-[var(--sig-500)] block">
            <h3 class="font-display font-semibold text-base mb-1">Staff import</h3>
            <p class="note">CSV / XLSX with preview.</p>
        </a>
        <a href="{{ route('admin.integrations') }}" class="card p-5 hover:border-[var(--sig-500)] block">
            <h3 class="font-display font-semibold text-base mb-1">Integrations</h3>
            <p class="note">Drive connect, health, Sync now.</p>
        </a>
        <a href="{{ route('admin.quick-links') }}" class="card p-5 hover:border-[var(--sig-500)] block">
            <h3 class="font-display font-semibold text-base mb-1">Quick links</h3>
            <p class="note">Email, Zenzap, platform SSO launches.</p>
        </a>
        <a href="{{ route('admin.settings') }}" class="card p-5 hover:border-[var(--sig-500)] block">
            <h3 class="font-display font-semibold text-base mb-1">Settings</h3>
            <p class="note">Site name, idle timeout, privacy contact.</p>
        </a>
        <a href="{{ route('admin.compliance') }}" class="card p-5 hover:border-[var(--sig-500)] block">
            <h3 class="font-display font-semibold text-base mb-1">Compliance</h3>
            <p class="note">Subject-access + audit export.</p>
        </a>
    </div>

    <div class="card p-5 space-y-4">
        <div>
            <h3 class="font-display font-semibold text-base mb-2">Modules</h3>
            <table class="w-full text-[13.5px]">
                <thead>
                <tr class="text-left text-[11px] uppercase tracking-wider text-[var(--ink-500)] border-b-2 border-[var(--line-strong)]">
                    <th class="py-2 px-3">Name</th>
                    <th class="py-2 px-3">Enabled</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($modules as $module)
                    <tr class="border-b border-[var(--line)]">
                        <td class="py-3 px-3 font-mono text-xs">{{ $module->name }}</td>
                        <td class="py-3 px-3">
                            @if ($module->is_enabled)
                                <span class="badge badge-ok">Enabled</span>
                            @else
                                <span class="badge badge-err">Disabled</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div>
            <h3 class="font-display font-semibold text-base mb-2">Site settings</h3>
            <p class="note">Session idle timeout: <span class="font-mono">{{ $idleTimeout }}</span> minutes</p>
        </div>
    </div>
@endsection
