@extends('layouts.app')

@section('title', 'Directory')

@section('breadcrumb')
    <span class="text-[var(--ink-900)] font-medium">Directory</span>
@endsection

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <div class="eyebrow font-mono text-[11px] tracking-widest uppercase text-signal-500 mb-2">People</div>
            <h1 class="font-display font-bold text-4xl tracking-tight" style="letter-spacing: -0.03em">Directory</h1>
            <p class="font-voice text-lg text-[var(--ink-700)] max-w-[62ch] mt-2">
                Find colleagues by name, department, role, or expertise.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('directory.org-chart') }}" class="btn btn-ghost btn-sm">Org chart</a>
            <a href="{{ route('directory.profile.edit') }}" class="btn btn-ghost btn-sm">Edit my profile</a>
            @can('directory.import')
                <a href="{{ route('directory.import') }}" class="btn btn-primary btn-sm">Import staff</a>
            @endcan
        </div>
    </div>

    <livewire:directory.index />
@endsection
