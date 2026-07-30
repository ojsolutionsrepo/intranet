<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="system">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Install') — OJ Intranet</title>
    <x-theme-boot />
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('css/oj.css') }}">
    @endif
    <style>
        .install-wrap { width: 100%; max-width: 640px; margin: 0 auto; }
        .steps { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
        .step-pill { font-family: var(--font-mono); font-size: 11px; padding: 4px 10px; border-radius: 999px; border: 1px solid var(--line-strong); color: var(--ink-500); }
        .step-pill.is-active { background: var(--sig-100); border-color: var(--sig-500); color: var(--sig-600); font-weight: 600; }
        .step-pill.is-done { background: var(--ok-100); border-color: var(--ok-600); color: var(--ok-600); }
        .check-row { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); font-size: 14px; }
        .check-row:last-child { border-bottom: 0; }
        .field { margin-bottom: 16px; }
        .field label { display: block; font-size: 12.5px; font-weight: 600; color: var(--ink-700); margin-bottom: 6px; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6" style="background: var(--paper-1)">
<div class="install-wrap">
    <div class="flex items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-md bg-signal-500 text-oj-900 font-display font-bold grid place-items-center">OJ</div>
            <div>
                <div class="font-display font-semibold text-lg">OJ Intranet setup</div>
                <div class="font-mono text-[11px] text-[var(--ink-500)]">First-run installer</div>
            </div>
        </div>
        <x-theme-toggle compact />
    </div>

    @php $step = $step ?? 1; @endphp
    <div class="steps">
        <span class="step-pill {{ $step > 1 ? 'is-done' : ($step === 1 ? 'is-active' : '') }}">1 Requirements</span>
        <span class="step-pill {{ $step > 2 ? 'is-done' : ($step === 2 ? 'is-active' : '') }}">2 Database</span>
        <span class="step-pill {{ $step > 3 ? 'is-done' : ($step === 3 ? 'is-active' : '') }}">3 Admin</span>
        <span class="step-pill {{ $step === 4 ? 'is-active' : '' }}">4 Done</span>
    </div>

    <div class="card p-6">
        @if ($errors->any())
            <div class="alert alert-err mb-4">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        @yield('content')
    </div>
</div>
<script src="{{ asset('js/theme.js') }}" defer></script>
</body>
</html>
