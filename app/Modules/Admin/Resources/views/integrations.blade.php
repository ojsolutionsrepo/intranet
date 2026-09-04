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
        <p class="note mb-4">Connect a Google account with <strong>read, update, and write</strong> access. When connected, <strong>document uploads are mirrored to Google Drive</strong> (local cache always kept). This is <strong>Google Drive only</strong> — Microsoft OneDrive is not supported. Document listings still use the intranet ACL — credentials alone are not enough; you must click Connect.</p>

        <ul class="note text-[12.5px] space-y-1.5 mb-4 font-mono">
            <li>
                <span class="badge badge-{{ $driveEnabled ? 'ok' : 'warn' }}">{{ $driveEnabled ? 'On' : 'Off' }}</span>
                DRIVE_BROKER_ENABLED
            </li>
            <li>
                <span class="badge badge-{{ $driveConfigured ? 'ok' : 'warn' }}">{{ $driveConfigured ? 'Set' : 'Missing' }}</span>
                GOOGLE_DRIVE_CLIENT_ID / SECRET
            </li>
            <li>
                <span class="badge badge-{{ $driveFolderId ? 'ok' : 'warn' }}">{{ $driveFolderId ? 'Set' : 'Optional' }}</span>
                GOOGLE_DRIVE_FOLDER_ID
            </li>
        </ul>

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
        @elseif ($driveConfigured)
            <a href="{{ route('drive.oauth.redirect') }}" class="btn btn-primary btn-sm">Connect Google Drive</a>
        @else
            <p class="note mb-3">Enter OAuth credentials below to enable the connect button. Create a Web application client in Google Cloud with Drive API enabled.</p>
        @endif

        <div class="mt-5 pt-5 border-t border-[var(--line)]">
            <h3 class="font-display font-semibold text-sm mb-3">OAuth credentials</h3>
            <livewire:admin.drive-credentials />
        </div>
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
