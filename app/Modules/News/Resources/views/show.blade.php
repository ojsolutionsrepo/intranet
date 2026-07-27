@extends('layouts.app')

@section('title', $post->title)

@section('breadcrumb')
    <a href="{{ route('news.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">News</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">{{ $post->title }}</span>
@endsection

@section('content')
    <article class="max-w-[72ch]">
        <div class="flex flex-wrap gap-2 mb-3">
            @if ($post->is_pinned)
                <span class="badge badge-warn">Pinned</span>
            @endif
            @if ($post->is_alert)
                <span class="badge badge-err">Alert</span>
            @endif
            <span class="badge badge-info">{{ $post->category }}</span>
        </div>
        <h1 class="font-display font-bold text-4xl tracking-tight mb-3" style="letter-spacing: -0.03em">{{ $post->title }}</h1>
        <p class="note mb-6">
            {{ $post->author?->name }} · {{ optional($post->published_at)->format('j M Y, H:i') }}
        </p>
        @if ($post->summary)
            <p class="font-voice text-lg text-[var(--ink-700)] mb-6">{{ $post->summary }}</p>
        @endif
        <div class="prose text-[var(--ink-900)] leading-relaxed space-y-3">
            {!! $post->body_html !!}
        </div>
    </article>
@endsection
