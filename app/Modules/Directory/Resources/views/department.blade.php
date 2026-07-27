@extends('layouts.app')

@section('title', $department->name)

@section('breadcrumb')
    <a href="{{ route('directory.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Directory</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">{{ $department->name }}</span>
@endsection

@section('content')
    <div class="eyebrow font-mono text-[11px] tracking-widest uppercase text-signal-500 mb-2">Department</div>
    <h1 class="font-display font-bold text-4xl tracking-tight mb-2" style="letter-spacing: -0.03em">{{ $department->name }}</h1>
    <p class="font-voice text-lg text-[var(--ink-700)] max-w-[62ch] mb-6">{{ $department->description }}</p>

    <div class="grid gap-4 md:grid-cols-3 mb-8">
        <div class="card p-4">
            <div class="text-xs uppercase tracking-wider text-[var(--ink-500)] mb-1">Lead</div>
            @if ($department->lead)
                <a href="{{ route('directory.show', $department->lead) }}" class="font-display font-semibold hover:text-[var(--sig-600)]">{{ $department->lead->name }}</a>
            @else
                <span class="note">Not assigned</span>
            @endif
        </div>
        <div class="card p-4">
            <div class="text-xs uppercase tracking-wider text-[var(--ink-500)] mb-1">Headcount</div>
            <div class="font-display font-semibold text-2xl">{{ $department->users->count() }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs uppercase tracking-wider text-[var(--ink-500)] mb-1">Teams</div>
            <div class="font-display font-semibold text-2xl">{{ $department->teams->count() }}</div>
        </div>
    </div>

    @if ($department->teams->isNotEmpty())
        <h2 class="font-display font-semibold text-xl mb-3">Teams</h2>
        <div class="grid gap-3 md:grid-cols-2 mb-8">
            @foreach ($department->teams as $team)
                <div class="card p-4">
                    <h3 class="font-display font-semibold">{{ $team->name }}</h3>
                    <p class="note mt-1">{{ $team->description }}</p>
                    <p class="text-xs text-[var(--ink-500)] mt-2">{{ $team->users->count() }} members</p>
                </div>
            @endforeach
        </div>
    @endif

    <h2 class="font-display font-semibold text-xl mb-3">People</h2>
    <div class="card overflow-hidden">
        <table class="w-full text-[13.5px]">
            <thead>
            <tr class="text-left text-[11px] uppercase tracking-wider text-[var(--ink-500)] border-b-2 border-[var(--line-strong)]">
                <th class="py-2 px-3">Name</th>
                <th class="py-2 px-3">Title</th>
                <th class="py-2 px-3">Ext</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($department->users->sortBy('name') as $person)
                <tr class="border-b border-[var(--line)]">
                    <td class="py-3 px-3">
                        <a href="{{ route('directory.show', $person) }}" class="font-medium hover:text-[var(--sig-600)]">{{ $person->name }}</a>
                    </td>
                    <td class="py-3 px-3">{{ $person->pivot->job_title ?: '—' }}</td>
                    <td class="py-3 px-3 font-mono text-xs">{{ $person->profile?->extension ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
