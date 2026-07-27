@extends('layouts.app')

@section('title', $user->name)

@section('breadcrumb')
    <a href="{{ route('directory.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Directory</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">{{ $user->name }}</span>
@endsection

@section('content')
    <div class="flex flex-wrap gap-6 items-start mb-8">
        <div class="w-24 h-24 rounded-full bg-oj-800 text-white grid place-items-center font-display font-bold text-3xl overflow-hidden shrink-0">
            @if ($user->profile?->photoUrl())
                <img src="{{ $user->profile->photoUrl() }}" alt="" class="w-full h-full object-cover">
            @else
                {{ strtoupper(substr($user->name, 0, 1)) }}
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <h1 class="font-display font-bold text-4xl tracking-tight" style="letter-spacing: -0.03em">{{ $user->name }}</h1>
            <p class="text-[var(--ink-700)] mt-1">{{ $user->jobTitle() ?: 'Team member' }}
                @if ($user->primaryDepartment())
                    · <a href="{{ route('directory.department', $user->primaryDepartment()) }}" class="text-[var(--sig-600)] hover:underline">{{ $user->primaryDepartment()->name }}</a>
                @endif
            </p>
            <div class="flex flex-wrap gap-2 mt-3">
                @foreach ($user->getRoleNames() as $role)
                    <span class="badge badge-info">{{ $role }}</span>
                @endforeach
                @foreach ($user->expertiseTags() as $tag)
                    <span class="badge badge-ok">{{ $tag }}</span>
                @endforeach
            </div>
            @if (auth()->id() === $user->id)
                <a href="{{ route('directory.profile.edit') }}" class="btn btn-ghost btn-sm mt-4">Edit profile</a>
            @endif
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="card p-5 space-y-3">
            <h2 class="font-display font-semibold text-base">Contact</h2>
            <p class="text-sm"><span class="text-[var(--ink-500)]">Email</span><br>
                <a class="text-[var(--sig-600)]" href="mailto:{{ $user->email }}">{{ $user->email }}</a>
            </p>
            <p class="text-sm"><span class="text-[var(--ink-500)]">Phone</span><br>
                @if ($user->profile?->phone)
                    <a class="text-[var(--sig-600)]" href="tel:{{ preg_replace('/\s+/', '', $user->profile->phone) }}">{{ $user->profile->phone }}</a>
                @else
                    —
                @endif
            </p>
            <p class="text-sm"><span class="text-[var(--ink-500)]">Extension</span><br>
                <span class="font-mono">{{ $user->profile?->extension ?: '—' }}</span>
            </p>
            <p class="text-sm"><span class="text-[var(--ink-500)]">Location</span><br>{{ $user->profile?->location ?: '—' }}</p>
        </div>
        <div class="card p-5 space-y-3">
            <h2 class="font-display font-semibold text-base">About</h2>
            <p class="text-sm text-[var(--ink-700)]">{{ $user->profile?->bio ?: 'No bio yet.' }}</p>
            <p class="text-sm"><span class="text-[var(--ink-500)]">Start date</span><br>
                {{ $user->profile?->start_date?->format('j M Y') ?: '—' }}
            </p>
            <p class="text-sm"><span class="text-[var(--ink-500)]">Teams</span><br>
                {{ $user->teams->pluck('name')->join(', ') ?: '—' }}
            </p>
        </div>
        <div class="card p-5 space-y-3 md:col-span-2">
            <h2 class="font-display font-semibold text-base">Reporting line</h2>
            <p class="text-sm">
                <span class="text-[var(--ink-500)]">Reports to</span><br>
                @if ($user->manager)
                    <a href="{{ route('directory.show', $user->manager) }}" class="text-[var(--sig-600)] hover:underline">{{ $user->manager->name }}</a>
                @else
                    —
                @endif
            </p>
            @if ($user->directReports->isNotEmpty())
                <div>
                    <span class="text-sm text-[var(--ink-500)]">Direct reports</span>
                    <ul class="mt-2 flex flex-wrap gap-2">
                        @foreach ($user->directReports as $report)
                            <li>
                                <a href="{{ route('directory.show', $report) }}" class="badge badge-info">{{ $report->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endsection
