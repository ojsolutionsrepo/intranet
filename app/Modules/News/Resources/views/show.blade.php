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
        <div class="prose news-body text-[var(--ink-900)] leading-relaxed space-y-3">
            {!! $post->body_html !!}
        </div>

        @if ($post->attachments->isNotEmpty())
            <section class="mt-8 pt-6 border-t border-[var(--line)]">
                <h2 class="font-display font-semibold text-lg mb-3">Attachments</h2>
                @php
                    $images = $post->attachments->where('is_image', true);
                    $files = $post->attachments->where('is_image', false);
                @endphp

                @if ($images->isNotEmpty())
                    <div class="grid gap-3 sm:grid-cols-2 mb-4">
                        @foreach ($images as $attachment)
                            <a href="{{ route('news.attachments.download', [$post, $attachment]) }}" class="block rounded-md border border-[var(--line)] overflow-hidden bg-[var(--paper-2)]">
                                <img src="{{ $attachment->url() }}" alt="{{ $attachment->original_name }}" class="w-full max-h-64 object-contain bg-[var(--paper-1)]">
                                <span class="block px-3 py-2 text-xs text-[var(--ink-500)] truncate">{{ $attachment->original_name }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($files->isNotEmpty())
                    <ul class="space-y-2">
                        @foreach ($files as $attachment)
                            <li>
                                <a href="{{ route('news.attachments.download', [$post, $attachment]) }}" class="flex items-center justify-between gap-3 rounded-md border border-[var(--line)] bg-[var(--paper-2)] px-3 py-2 text-sm hover:border-[var(--sig-500)]">
                                    <span class="truncate font-medium">{{ $attachment->original_name }}</span>
                                    <span class="note text-xs shrink-0">{{ $attachment->humanSize() }} · Download</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif
    </article>
@endsection
