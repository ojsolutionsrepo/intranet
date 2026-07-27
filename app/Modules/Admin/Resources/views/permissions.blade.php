@extends('layouts.app')

@section('title', 'Permissions')

@section('breadcrumb')
    <a href="{{ route('admin.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Administration</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Permissions</span>
@endsection

@section('content')
    <h1 class="font-display font-bold text-3xl tracking-tight mb-2">Permission matrix</h1>
    <p class="note mb-6">Toggle permissions per role. Saves take effect immediately.</p>
    <livewire:admin.permission-matrix />
@endsection
