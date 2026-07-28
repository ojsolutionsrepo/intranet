@extends('layouts.app')

@section('title', $project->name)

@section('breadcrumb')
    <a href="{{ route('projects.index') }}" class="hover:text-[var(--ink-900)]">Projects</a>
    <span class="mx-1.5 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">{{ $project->name }}</span>
@endsection

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <div class="eyebrow font-mono text-[11px] tracking-widest uppercase text-signal-500 mb-2">{{ $project->source }}</div>
            <h1 class="font-display font-bold text-4xl tracking-tight" style="letter-spacing: -0.03em">{{ $project->name }}</h1>
            <div class="flex flex-wrap items-center gap-2 mt-3">
                @if ($project->rag)
                    <span class="badge badge-{{ $project->ragBadge() }}">{{ strtoupper($project->rag) }}</span>
                @endif
                <span class="badge badge-info">{{ $project->status }}</span>
                @if ($project->isStale())
                    <span class="badge badge-warn">Data may be stale (synced {{ $project->synced_at?->diffForHumans() ?? 'never' }})</span>
                @endif
            </div>
        </div>
        @if ($project->deep_link)
            <a href="{{ $project->deep_link }}" class="btn btn-secondary btn-sm" target="_blank" rel="noopener">Open in source</a>
        @endif
    </div>

    @if ($project->summary)
        <p class="font-voice text-lg text-[var(--ink-700)] max-w-[62ch] mb-8">{{ $project->summary }}</p>
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <section>
            <h2 class="font-display font-semibold text-lg mb-3">Milestones</h2>
            @if ($project->milestones->isEmpty())
                <p class="note">No milestones synced yet.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($project->milestones as $ms)
                        <li class="flex items-center justify-between border-b border-[var(--line)] py-2 text-[13.5px]">
                            <span>{{ $ms->title }}</span>
                            <span class="note">{{ $ms->due_on?->format('d M Y') ?? '—' }} · {{ $ms->status }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
        <section>
            <h2 class="font-display font-semibold text-lg mb-3">Metrics</h2>
            @php $metrics = $project->metrics ?? []; @endphp
            @if (empty($metrics))
                <p class="note">No metrics available.</p>
            @else
                <dl class="space-y-2 text-[13.5px]">
                    @foreach ($metrics as $key => $value)
                        <div class="flex justify-between border-b border-[var(--line)] py-2">
                            <dt class="note">{{ $key }}</dt>
                            <dd class="font-mono">{{ is_scalar($value) ? $value : json_encode($value) }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
            <p class="note mt-4">Last sync: {{ $project->synced_at?->toDayDateTimeString() ?? 'Never' }}</p>
        </section>
    </div>
@endsection
