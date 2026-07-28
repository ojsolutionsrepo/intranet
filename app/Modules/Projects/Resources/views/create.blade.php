@extends('layouts.app')

@section('title', 'Add project')

@section('breadcrumb')
    <a href="{{ route('projects.index') }}" class="hover:text-[var(--ink-900)]">Projects</a>
    <span class="mx-1.5 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Add</span>
@endsection

@section('content')
    <div class="mb-6">
        <h1 class="font-display font-bold text-3xl tracking-tight">Add project</h1>
        <p class="note mt-2">Manual projects appear on the staff Projects list and My Projects dashboard widget.</p>
    </div>
    <div class="card p-5">
        <livewire:projects.form />
    </div>
@endsection
