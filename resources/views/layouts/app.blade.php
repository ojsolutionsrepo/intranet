<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="system">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'OJ Intranet'))</title>
    <x-theme-boot />
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('css/oj.css') }}">
    @endif
    @livewireStyles
</head>
<body>
@php
    $menu = app(\App\Core\Modules\ModuleRegistry::class)->menuItems();
    $siteName = app(\App\Shared\Services\Settings::class)->get('site_name', 'OJ Intranet');
@endphp
<div class="min-h-screen grid md:grid-cols-[240px_1fr]">
    <aside class="bg-oj-900 text-oj-100 p-5 md:sticky md:top-0 md:h-screen md:overflow-y-auto border-r border-oj-800">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-8 h-8 rounded-md bg-signal-500 text-oj-900 font-display font-bold grid place-items-center text-base">OJ</div>
            <div>
                <div class="font-display font-semibold text-[15px] tracking-tight">{{ $siteName }}</div>
                <div class="font-mono text-[11px] text-oj-400">v0.1 · INTRANET</div>
            </div>
        </div>

        <nav class="space-y-5">
            <div>
                <div class="text-[10px] uppercase tracking-[0.12em] text-oj-400 font-semibold mb-2 px-2">Main</div>
                <a href="{{ route('dashboard') }}"
                   class="block px-3 py-2 rounded-sm text-[13.5px] mb-0.5 {{ request()->routeIs('dashboard') ? 'bg-oj-800 text-white' : 'text-oj-200 hover:bg-oj-800 hover:text-white' }}">
                    Dashboard
                </a>
                @foreach ($menu as $item)
                    @if (empty($item['permission']) || auth()->user()?->can($item['permission']))
                        <a href="{{ route($item['route']) }}"
                           class="block px-3 py-2 rounded-sm text-[13.5px] mb-0.5 {{ request()->routeIs($item['route']) ? 'bg-oj-800 text-white' : 'text-oj-200 hover:bg-oj-800 hover:text-white' }}">
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>
            @role('Admin')
            <div>
                <div class="text-[10px] uppercase tracking-[0.12em] text-oj-400 font-semibold mb-2 px-2">Admin</div>
                <a href="{{ route('admin.index') }}"
                   class="block px-3 py-2 rounded-sm text-[13.5px] mb-0.5 {{ request()->routeIs('admin.index') ? 'bg-oj-800 text-white' : 'text-oj-200 hover:bg-oj-800 hover:text-white' }}">
                    Administration
                </a>
                <a href="{{ route('admin.users.index') }}"
                   class="block px-3 py-2 rounded-sm text-[13.5px] mb-0.5 {{ request()->routeIs('admin.users.*') ? 'bg-oj-800 text-white' : 'text-oj-200 hover:bg-oj-800 hover:text-white' }}">
                    Users
                </a>
                <a href="{{ route('admin.permissions') }}"
                   class="block px-3 py-2 rounded-sm text-[13.5px] mb-0.5 {{ request()->routeIs('admin.permissions') ? 'bg-oj-800 text-white' : 'text-oj-200 hover:bg-oj-800 hover:text-white' }}">
                    Permissions
                </a>
            </div>
            @endrole
        </nav>
    </aside>

    <div class="flex flex-col min-w-0">
        <header class="bg-paper-0 border-b border-[var(--line)] px-5 md:px-8 py-3 flex items-center justify-between gap-4">
            <div class="text-sm text-ink-500">
                @hasSection('breadcrumb')
                    @yield('breadcrumb')
                @else
                    <span class="font-mono text-xs">{{ request()->path() }}</span>
                @endif
            </div>
            <div class="flex items-center gap-3 text-sm">
                <x-theme-toggle compact />
                <span class="text-ink-700">{{ auth()->user()?->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">Sign out</button>
                </form>
            </div>
        </header>

        <main class="p-5 md:p-8 max-w-[1100px] w-full">
            @if (session('status'))
                <div class="alert alert-info mb-4">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-err mb-4">
                    <ul class="list-disc pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
<script src="{{ asset('js/theme.js') }}" defer></script>
@livewireScripts
</body>
</html>
