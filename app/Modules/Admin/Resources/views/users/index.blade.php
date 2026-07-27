@extends('layouts.app')

@section('title', 'Users')

@section('breadcrumb')
    <a href="{{ route('admin.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Administration</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Users</span>
@endsection

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="font-display font-bold text-3xl tracking-tight">Users</h1>
            <p class="note">Create, edit, and deactivate accounts. Deactivation ends live sessions.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">Add user</a>
    </div>
    <livewire:admin.user-index />
@endsection
