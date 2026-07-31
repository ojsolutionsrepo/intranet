@extends('layouts.app')

@section('title', 'Departments')

@section('breadcrumb')
    <a href="{{ route('admin.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Administration</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Departments</span>
@endsection

@section('content')
    <div class="page-form-wide">
        <div class="mb-6">
            <h1 class="font-display font-bold text-3xl tracking-tight">Departments</h1>
            <p class="note">Create and edit departments used by Directory, audiences, and staff profiles.</p>
        </div>
        <livewire:admin.department-manager />
    </div>
@endsection
