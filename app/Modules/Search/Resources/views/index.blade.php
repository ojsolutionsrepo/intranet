@extends('layouts.app')

@section('title', 'Search')

@section('breadcrumb')
    <span class="text-[var(--ink-900)] font-medium">Search</span>
@endsection

@section('content')
    <div class="eyebrow font-mono text-[11px] tracking-widest uppercase text-signal-500 mb-2">Find anything</div>
    <h1 class="font-display font-bold text-4xl tracking-tight mb-2" style="letter-spacing: -0.03em">Search</h1>
    <p class="font-voice text-lg text-[var(--ink-700)] max-w-[62ch] mb-6">Across posts, documents, people, departments, events, and projects — filtered by your access.</p>
    <livewire:search.page />
@endsection
