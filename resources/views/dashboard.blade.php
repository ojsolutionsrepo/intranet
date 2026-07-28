@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
    <span class="text-[var(--ink-900)] font-medium">Dashboard</span>
@endsection

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <div class="eyebrow font-mono text-[11px] tracking-widest uppercase text-signal-500 mb-2">Home</div>
            <h1 class="font-display font-bold text-4xl tracking-tight" style="letter-spacing: -0.03em">Welcome back</h1>
            <p class="font-voice text-lg text-[var(--ink-700)] max-w-[62ch] mt-2">
                Your widgets are filtered by access — a broken widget never takes down the page.
            </p>
        </div>
        <a href="{{ route('search.index') }}" class="btn btn-ghost btn-sm" title="Ctrl/Cmd+K">Search ⌘K</a>
    </div>

    <livewire:dashboard.shell />
@endsection
