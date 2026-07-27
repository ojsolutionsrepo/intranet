@extends('layouts.app')

@section('title', 'Org chart')

@section('breadcrumb')
    <a href="{{ route('directory.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Directory</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Org chart</span>
@endsection

@section('content')
    <h1 class="font-display font-bold text-3xl tracking-tight mb-2">Org chart</h1>
    <p class="note mb-6">Collapsible reporting tree. Should-priority for Gate 1.</p>
    <livewire:directory.org-chart />
@endsection
