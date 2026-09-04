@extends('layouts.app')

@section('title', $document->title)

@section('breadcrumb')
    <a href="{{ route('documents.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Documents</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    @foreach ($document->category?->breadcrumbs() ?? [] as $crumb)
        <span class="text-[var(--ink-500)]">{{ $crumb->name }}</span>
        <span class="mx-2 text-[var(--ink-400)]">/</span>
    @endforeach
    <span class="text-[var(--ink-900)] font-medium">{{ $document->title }}</span>
@endsection

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="font-display font-bold text-3xl tracking-tight mb-2">{{ $document->title }}</h1>
            <p class="note">
                Owner: {{ $document->owner?->name }}
                · Current: <span class="badge badge-ok">v{{ $document->currentVersion?->version_number }}</span>
                @if ($document->is_policy)
                    · <span class="badge badge-info">Policy</span>
                    @php $chip = $document->reviewStatus(); @endphp
                    · <span class="badge {{ $chip === 'overdue' ? 'badge-err' : ($chip === 'due' ? 'badge-warn' : 'badge-ok') }}">{{ ucfirst($chip) }}</span>
                @endif
            </p>
        </div>
        @if ($document->currentVersion)
            <a href="{{ route('documents.download', $document) }}" class="btn btn-primary btn-sm">Download current</a>
        @endif
    </div>

    <h2 class="font-display font-semibold text-xl mb-3">Version history</h2>
    <div class="card overflow-hidden mb-6">
        <table class="w-full text-[13.5px]">
            <thead>
            <tr class="text-left text-[11px] uppercase tracking-wider text-[var(--ink-500)] border-b-2 border-[var(--line-strong)]">
                <th class="py-2 px-3">Ver</th>
                <th class="py-2 px-3">File</th>
                <th class="py-2 px-3">Checksum</th>
                <th class="py-2 px-3">By</th>
                <th class="py-2 px-3">Notes</th>
                <th class="py-2 px-3"></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($document->versions as $version)
                <tr class="border-b border-[var(--line)]">
                    <td class="py-3 px-3 font-mono">
                        v{{ $version->version_number }}
                        @if ($version->isCurrent())
                            <span class="badge badge-ok">current</span>
                        @endif
                    </td>
                    <td class="py-3 px-3">{{ $version->original_filename }}</td>
                    <td class="py-3 px-3 font-mono text-[11px]">{{ substr($version->checksum_sha256, 0, 12) }}…</td>
                    <td class="py-3 px-3">{{ $version->uploader?->name }}</td>
                    <td class="py-3 px-3">{{ $version->changelog }}</td>
                    <td class="py-3 px-3 text-right whitespace-nowrap">
                        <a href="{{ route('documents.download-version', [$document, $version]) }}" class="btn btn-ghost btn-sm">Download</a>
                        @can('documents.upload')
                            @if (! $version->isCurrent())
                                <form method="POST" action="{{ route('documents.restore-version', [$document, $version]) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm">Restore as new</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if ($document->currentVersion?->mime === 'application/pdf' || str_ends_with(strtolower($document->currentVersion?->original_filename ?? ''), '.pdf'))
        <h2 class="font-display font-semibold text-xl mb-3">Preview</h2>
        <div class="card p-2 mb-6">
            <iframe
                title="PDF preview"
                class="w-full min-h-[480px] rounded-md border border-[var(--line)] bg-[var(--paper-2)]"
                src="{{ route('documents.preview', $document) }}"
            ></iframe>
            <p class="note mt-2 px-2">Preview opens the PDF in your browser. If it stays blank, use <a class="underline" href="{{ route('documents.download', $document) }}">Download</a>.</p>
        </div>
    @elseif ($document->currentVersion)
        <p class="note mb-6">In-browser preview is available for PDFs. Use Download for this file type.</p>
    @endif
@endsection
