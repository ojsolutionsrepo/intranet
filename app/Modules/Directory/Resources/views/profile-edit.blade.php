@extends('layouts.app')

@section('title', 'Edit profile')

@section('breadcrumb')
    <a href="{{ route('directory.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Directory</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Edit profile</span>
@endsection

@section('content')
    <div class="page-form">
        <h1 class="font-display font-bold text-3xl tracking-tight mb-2">Edit your profile</h1>
        <p class="note mb-6">You can update bio, photo, phone, location, and expertise. Role, department, and title are managed by Admin.</p>
        <livewire:directory.profile-edit />
    </div>
@endsection
