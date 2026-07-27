@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
    <span class="text-[var(--ink-900)] font-medium">Dashboard</span>
@endsection

@section('content')
    <div class="eyebrow font-mono text-[11px] tracking-widest uppercase text-signal-500 mb-2">Home</div>
    <h1 class="font-display font-bold text-4xl tracking-tight mb-3" style="letter-spacing: -0.03em">Welcome back</h1>
    <p class="font-voice text-lg text-[var(--ink-700)] max-w-[62ch] mb-6">
        This space is where the whole company stays in step — announcements, documents, and project updates live here.
    </p>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="card p-5">
            <h3 class="font-display font-semibold text-base mb-2">Getting started</h3>
            <p class="note">Directory is live — find people by name, department, or expertise.</p>
            <a href="{{ route('directory.index') }}" class="btn btn-primary btn-sm mt-3">Open directory</a>
        </div>
        <div class="card p-5">
            <h3 class="font-display font-semibold text-base mb-2">Your role</h3>
            <p class="note mb-3">Signed in as <strong>{{ auth()->user()->name }}</strong></p>
            <div class="flex flex-wrap gap-2">
                @foreach (auth()->user()->getRoleNames() as $role)
                    <span class="badge badge-info">{{ $role }}</span>
                @endforeach
            </div>
        </div>
    </div>
@endsection
