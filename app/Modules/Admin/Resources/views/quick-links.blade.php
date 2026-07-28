@extends('layouts.app')

@section('title', 'Quick links')

@section('breadcrumb')
    <a href="{{ route('admin.index') }}" class="hover:text-[var(--ink-900)]">Administration</a>
    <span class="mx-1.5 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Quick links</span>
@endsection

@section('content')
    <div class="mb-6">
        <div class="eyebrow font-mono text-[11px] tracking-widest uppercase text-signal-500 mb-2">Dashboard</div>
        <h1 class="font-display font-bold text-3xl tracking-tight">Quick links</h1>
        <p class="note mt-2">Email, Zenzap, Drive, Plane, and other platform links shown on every staff dashboard.</p>
    </div>

    @if (session('status'))
        <p class="badge badge-ok mb-4">{{ session('status') }}</p>
    @endif

    <div class="card p-5">
        <livewire:admin.quick-link-manager />
    </div>
@endsection
