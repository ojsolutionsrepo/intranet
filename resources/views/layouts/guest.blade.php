<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="system">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') — {{ config('app.name', 'OJ Intranet') }}</title>
    <x-theme-boot />
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('css/oj.css') }}">
    @endif
</head>
<body class="min-h-screen flex items-center justify-center p-6" style="background: var(--paper-1)">
<div class="w-full max-w-md">
    <div class="flex items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-md bg-signal-500 text-oj-900 font-display font-bold grid place-items-center">OJ</div>
            <div>
                <div class="font-display font-semibold text-lg">OJ Intranet</div>
                <div class="font-mono text-[11px] text-[var(--ink-500)]">Staff portal</div>
            </div>
        </div>
        <x-theme-toggle compact />
    </div>
    <div class="card p-6">
        @if ($errors->any())
            <div class="alert alert-err mb-4">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif
        @if (session('status'))
            <div class="alert alert-info mb-4">{{ session('status') }}</div>
        @endif
        @yield('content')
    </div>
</div>
<script src="{{ asset('js/theme.js') }}" defer></script>
</body>
</html>
