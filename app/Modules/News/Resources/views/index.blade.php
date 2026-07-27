@extends('layouts.app')

@section('title', 'News')

@section('breadcrumb')
    <span class="text-[var(--ink-900)] font-medium">News</span>
@endsection

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <div class="eyebrow font-mono text-[11px] tracking-widest uppercase text-signal-500 mb-2">Comms</div>
            <h1 class="font-display font-bold text-4xl tracking-tight" style="letter-spacing: -0.03em">News</h1>
            <p class="font-voice text-lg text-[var(--ink-700)] max-w-[62ch] mt-2">Announcements and updates for your audience.</p>
        </div>
        @can('news.publish')
            <a href="{{ route('news.create') }}" class="btn btn-primary btn-sm">New post</a>
        @endcan
    </div>
    <livewire:news.feed />
@endsection
