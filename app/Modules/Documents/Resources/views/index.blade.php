@extends('layouts.app')

@section('title', 'Documents')

@section('breadcrumb')
    <span class="text-[var(--ink-900)] font-medium">Documents</span>
@endsection

@section('content')
    @if (session('status'))
        <p class="badge badge-ok mb-4">{{ session('status') }}</p>
    @endif
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <div class="eyebrow font-mono text-[11px] tracking-widest uppercase text-signal-500 mb-2">Library</div>
            <h1 class="font-display font-bold text-4xl tracking-tight" style="letter-spacing: -0.03em">Documents</h1>
            <p class="font-voice text-lg text-[var(--ink-700)] max-w-[62ch] mt-2">Policies, templates, guides, and forms — versioned and access-controlled.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('documents.search') }}" class="btn btn-ghost btn-sm">Body search</a>
            @can('documents.upload')
                <a href="{{ route('documents.upload') }}" class="btn btn-primary btn-sm">Upload</a>
            @endcan
        </div>
    </div>
    <livewire:documents.browser />
@endsection
