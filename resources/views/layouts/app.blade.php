<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'OJ Intranet'))</title>
    <x-theme-boot />
    <script>
        (function () {
            try {
                var key = 'oj-sidebar';
                var stored = localStorage.getItem(key);
                if (stored !== 'collapsed' && stored !== 'expanded') {
                    stored = window.matchMedia('(max-width: 767px)').matches ? 'collapsed' : 'expanded';
                }
                document.documentElement.setAttribute('data-sidebar', stored);
            } catch (e) {
                document.documentElement.setAttribute('data-sidebar', 'expanded');
            }
        })();
    </script>
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('css/oj.css') }}">
    @endif
    {{-- After stylesheets so admin brand colour overrides :root tokens --}}
    <x-branding />
    @livewireStyles
</head>
<body>
@php
    $menu = app(\App\Core\Modules\ModuleRegistry::class)->menuItems();
    $settings = app(\App\Shared\Services\Settings::class);
    $branding = app(\App\Shared\Services\Branding::class);
    $siteName = $settings->get('site_name', 'OJ Intranet');
    $logoUrl = $branding->logoUrl();
@endphp

{{-- Drawer lives outside the shell so it overlays page content on mobile --}}
<div class="sidebar-drawer" data-sidebar-drawer>
    <div class="sidebar-backdrop" data-sidebar-close aria-hidden="true"></div>
    <aside class="sidebar" id="app-sidebar" aria-label="Main navigation">
        <div class="sidebar-brand">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="sidebar-logo">
            @else
                <div class="sidebar-mark" aria-hidden="true">OJ</div>
            @endif
            <div class="sidebar-brand-text">
                <div class="sidebar-title">{{ $siteName }}</div>
                <div class="sidebar-meta">v0.1 · INTRANET</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <div class="sidebar-section-label">Main</div>
                <a href="{{ route('dashboard') }}"
                   class="sidebar-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
                   title="Dashboard">
                    <x-nav-icon name="dashboard" />
                    <span class="sidebar-link-label">Dashboard</span>
                </a>
                @foreach ($menu as $item)
                    @if (empty($item['permission']) || auth()->user()?->can($item['permission']))
                        <a href="{{ route($item['route']) }}"
                           class="sidebar-link {{ request()->routeIs($item['route']) ? 'is-active' : '' }}"
                           title="{{ $item['label'] }}">
                            <x-nav-icon :name="$item['label']" />
                            <span class="sidebar-link-label">{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
            @role('Admin')
            <div class="sidebar-section">
                <div class="sidebar-section-label">Admin</div>
                <a href="{{ route('admin.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.index') ? 'is-active' : '' }}"
                   title="Administration">
                    <x-nav-icon name="admin" />
                    <span class="sidebar-link-label">Administration</span>
                </a>
                <a href="{{ route('admin.users.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}"
                   title="Users">
                    <x-nav-icon name="users" />
                    <span class="sidebar-link-label">Users</span>
                </a>
                <a href="{{ route('admin.departments') }}"
                   class="sidebar-link {{ request()->routeIs('admin.departments') ? 'is-active' : '' }}"
                   title="Departments">
                    <x-nav-icon name="departments" />
                    <span class="sidebar-link-label">Departments</span>
                </a>
                <a href="{{ route('admin.permissions') }}"
                   class="sidebar-link {{ request()->routeIs('admin.permissions') ? 'is-active' : '' }}"
                   title="Permissions">
                    <x-nav-icon name="permissions" />
                    <span class="sidebar-link-label">Permissions</span>
                </a>
            </div>
            @endrole
        </nav>
    </aside>
</div>

<div class="shell" data-shell>
    <div class="shell-main">
        <header class="shell-header">
            <div class="shell-header-left">
                <button type="button"
                        class="sidebar-toggle-btn"
                        data-sidebar-toggle
                        aria-controls="app-sidebar"
                        aria-expanded="true"
                        title="Collapse sidebar">
                    <span class="sr-only">Toggle sidebar</span>
                    {{-- Desktop: panel collapse / expand --}}
                    <svg class="sidebar-toggle-icon sidebar-toggle-icon-collapse" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <path d="M9 3v18"/>
                        <path d="m15 9-3 3 3 3"/>
                    </svg>
                    <svg class="sidebar-toggle-icon sidebar-toggle-icon-expand" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <path d="M9 3v18"/>
                        <path d="m13 15 3-3-3-3"/>
                    </svg>
                    {{-- Mobile: hamburger / close --}}
                    <svg class="sidebar-toggle-icon sidebar-toggle-icon-menu" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M4 7h16M4 12h16M4 17h16"/>
                    </svg>
                    <svg class="sidebar-toggle-icon sidebar-toggle-icon-close" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
                <div class="text-sm text-ink-500 shell-breadcrumb">
                    @hasSection('breadcrumb')
                        @yield('breadcrumb')
                    @else
                        <span class="font-mono text-xs">{{ request()->path() }}</span>
                    @endif
                </div>
            </div>
            <div class="shell-header-actions">
                <button type="button"
                        class="header-icon-btn"
                        onclick="window.dispatchEvent(new KeyboardEvent('keydown',{key:'k',ctrlKey:true}))"
                        title="Search (Ctrl/Cmd+K)"
                        aria-label="Search">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                </button>
                <x-theme-toggle compact />
                <div class="profile-menu" data-profile-menu>
                    <button type="button"
                            class="profile-menu-trigger"
                            data-profile-menu-toggle
                            aria-expanded="false"
                            aria-haspopup="true"
                            aria-controls="profile-menu-panel"
                            title="Profile menu">
                        <span class="profile-menu-avatar" aria-hidden="true">{{ strtoupper(\Illuminate\Support\Str::substr(auth()->user()?->name ?? 'U', 0, 1)) }}</span>
                        <span class="profile-menu-name">{{ auth()->user()?->name }}</span>
                        <svg class="profile-menu-caret" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </button>
                    <div id="profile-menu-panel" class="profile-menu-panel" data-profile-menu-panel hidden role="menu">
                        <a href="{{ route('directory.profile.edit') }}" class="profile-menu-item" role="menuitem">Edit profile</a>
                        @role('Admin')
                            <a href="{{ route('admin.settings') }}" class="profile-menu-item" role="menuitem">Settings</a>
                        @else
                            <a href="{{ route('privacy.notice') }}" class="profile-menu-item" role="menuitem">Settings</a>
                        @endrole
                        <form method="POST" action="{{ route('logout') }}" class="profile-menu-form">
                            @csrf
                            <button type="submit" class="profile-menu-item profile-menu-item-danger" role="menuitem">Sign out</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="shell-content">
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
@auth
    <livewire:search.omnibox />
@endauth
<script src="{{ asset('js/theme.js') }}" defer></script>
<script src="{{ asset('js/sidebar.js') }}" defer></script>
<script src="{{ asset('js/profile-menu.js') }}" defer></script>
<script src="{{ asset('js/rich-editor.js') }}"></script>
@livewireScripts
</body>
</html>
