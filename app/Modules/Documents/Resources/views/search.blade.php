@extends('layouts.app')

@section('title', 'Document search')

@section('breadcrumb')
    <a href="{{ route('documents.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Documents</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Search</span>
@endsection

@section('content')
    <div class="page-form-wide">
        <h1 class="font-display font-bold text-3xl tracking-tight mb-2">Search inside files</h1>
        <p class="note mb-6">Matches extracted text from PDF / Office / plain-text bodies (not just titles).</p>

        <form method="GET" class="card p-4 mb-5 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Phrase</label>
                <input type="search" name="q" value="{{ $q }}" class="input" placeholder="e.g. remote working">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <div class="space-y-3">
            @forelse ($results as $doc)
                <a href="{{ route('documents.show', $doc) }}" class="card p-4 block hover:border-[var(--sig-500)]">
                    <div class="font-display font-semibold">{{ $doc->title }}</div>
                    <div class="note">{{ $doc->category?->name }} · Owner {{ $doc->owner?->name }}</div>
                </a>
            @empty
                @if ($q !== '')
                    <div class="card p-5"><p class="note">No body matches for “{{ $q }}”.</p></div>
                @endif
            @endforelse
        </div>
    </div>
@endsection
