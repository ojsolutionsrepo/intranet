@extends('layouts.app')

@section('title', 'Projects')

@section('breadcrumb')
    <span class="text-[var(--ink-900)] font-medium">Projects</span>
@endsection

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <div class="eyebrow font-mono text-[11px] tracking-widest uppercase text-signal-500 mb-2">Delivery</div>
            <h1 class="font-display font-bold text-4xl tracking-tight" style="letter-spacing: -0.03em">Projects</h1>
            <p class="font-voice text-lg text-[var(--ink-700)] max-w-[62ch] mt-2">Active work from Plane, Governex, and manual entry — with freshness you can trust.</p>
        </div>
        @can('projects.manage')
            <a href="{{ route('projects.create') }}" class="btn btn-primary btn-sm">Add project</a>
        @endcan
    </div>
    <livewire:projects.index />
@endsection
