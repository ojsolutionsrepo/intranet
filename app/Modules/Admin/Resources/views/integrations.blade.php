@extends('layouts.app')

@section('title', 'Integration health')

@section('breadcrumb')
    <a href="{{ route('admin.index') }}" class="hover:text-[var(--ink-900)]">Administration</a>
    <span class="mx-1.5 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Integrations</span>
@endsection

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <div class="eyebrow font-mono text-[11px] tracking-widest uppercase text-signal-500 mb-2">Ops</div>
            <h1 class="font-display font-bold text-3xl tracking-tight">Integration health</h1>
            <p class="note mt-2">Circuit breakers and sync status. Core intranet stays up when an adapter is down.</p>
        </div>
        <form method="POST" action="{{ route('admin.integrations.sync') }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">Sync now</button>
        </form>
    </div>

    @if (session('status'))
        <p class="badge badge-ok mb-4">{{ session('status') }}</p>
    @endif
    @if (session('warning'))
        <p class="badge badge-warn mb-4">{{ session('warning') }}</p>
    @endif

    <div class="card p-5 mb-6">
        <h2 class="font-display font-semibold text-lg mb-2">Google Drive</h2>
        <p class="note mb-4">Connect a Google account with <strong>read, update, and write</strong> access. Document listings still use the intranet ACL; Drive is only used for file I/O and cache fill.</p>
        @if ($drive->isConnected() && $driveConnection)
            <p class="text-[13.5px] mb-3">
                Connected as <span class="font-mono">{{ $driveConnection->account_email }}</span>
                <span class="badge badge-ok ml-2">Active</span>
            </p>
            <form method="POST" action="{{ route('drive.oauth.disconnect') }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm">Disconnect</button>
            </form>
            <a href="https://drive.google.com" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">Open Drive</a>
        @else
            <a href="{{ route('drive.oauth.redirect') }}" class="btn btn-primary btn-sm">Connect Google Drive</a>
            <p class="note mt-3">Requires <span class="font-mono">GOOGLE_DRIVE_CLIENT_ID</span> / <span class="font-mono">SECRET</span> in .env (OAuth consent screen with Drive scopes).</p>
        @endif
    </div>

    <div class="overflow-x-auto card">
        <table class="w-full text-[13.5px]">
            <thead>
            <tr class="text-left text-[11px] uppercase tracking-wider text-[var(--ink-500)] border-b-2 border-[var(--line-strong)]">
                <th class="py-2 px-3">Integration</th>
                <th class="py-2 px-3">Driver</th>
                <th class="py-2 px-3">Status</th>
                <th class="py-2 px-3">Circuit</th>
                <th class="py-2 px-3">Last sync</th>
                <th class="py-2 px-3">Message</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($rows as $row)
                <tr class="border-b border-[var(--line)]">
                    <td class="py-3 px-3 font-mono text-xs">{{ $row['name'] }}</td>
                    <td class="py-3 px-3 font-mono text-xs">{{ $row['driver'] }}</td>
                    <td class="py-3 px-3">
                        <span class="badge badge-{{ $row['ok'] ? 'ok' : 'err' }}">{{ $row['status'] }}</span>
                    </td>
                    <td class="py-3 px-3 font-mono text-xs">{{ $row['circuit'] }}</td>
                    <td class="py-3 px-3 note">{{ $row['last_sync_at'] ?? '—' }}</td>
                    <td class="py-3 px-3 note">{{ $row['message'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
