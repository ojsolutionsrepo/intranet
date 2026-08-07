@extends('layouts.app')

@section('title', 'Site settings')

@section('breadcrumb')
    <a href="{{ route('admin.index') }}" class="hover:text-[var(--ink-900)]">Administration</a>
    <span class="mx-1.5 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Settings</span>
@endsection

@section('content')
    <div class="page-form">
        <div class="mb-6">
            <h1 class="font-display font-bold text-3xl tracking-tight">Site settings</h1>
            <p class="note mt-2">Branding (name, colour, logo, favicon), session timeout, and privacy contact (UR-ADM-05).</p>
        </div>
        @if (session('status'))
            <p class="badge badge-ok mb-4">{{ session('status') }}</p>
        @endif
        <div class="card p-5">
            <livewire:admin.site-settings />
        </div>
    </div>
@endsection
