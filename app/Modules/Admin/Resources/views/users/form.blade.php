@extends('layouts.app')

@section('title', $userId ? 'Edit user' : 'Add user')

@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Users</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">{{ $userId ? 'Edit' : 'Create' }}</span>
@endsection

@section('content')
    <h1 class="font-display font-bold text-3xl tracking-tight mb-6">{{ $userId ? 'Edit user' : 'Add user' }}</h1>
    <livewire:admin.user-form :user-id="$userId" />
@endsection
