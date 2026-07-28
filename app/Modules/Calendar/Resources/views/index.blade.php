@extends('layouts.app')

@section('title', 'Calendar')

@section('breadcrumb')
    <span class="text-[var(--ink-900)] font-medium">Calendar</span>
@endsection

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <div class="eyebrow font-mono text-[11px] tracking-widest uppercase text-signal-500 mb-2">Schedule</div>
            <h1 class="font-display font-bold text-4xl tracking-tight" style="letter-spacing: -0.03em">Calendar</h1>
            <p class="font-voice text-lg text-[var(--ink-700)] max-w-[62ch] mt-2">Month, week, and list views with audience targeting and ICS export.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('calendar.ics.download') }}" class="btn btn-ghost btn-sm">Download .ics</a>
            <form method="POST" action="{{ route('calendar.ics.token') }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm">Personal feed URL</button>
            </form>
            @can('calendar.manage')
                <a href="{{ route('calendar.create') }}" class="btn btn-primary btn-sm">New event</a>
            @endcan
        </div>
    </div>
    <livewire:calendar.board />
@endsection
