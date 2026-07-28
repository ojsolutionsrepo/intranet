@extends('layouts.app')

@section('title', 'New event')

@section('breadcrumb')
    <a href="{{ route('calendar.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Calendar</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Create</span>
@endsection

@section('content')
    <h1 class="font-display font-bold text-3xl tracking-tight mb-6">Create event</h1>
    <livewire:calendar.event-form />
@endsection
