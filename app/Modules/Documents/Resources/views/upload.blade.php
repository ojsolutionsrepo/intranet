@extends('layouts.app')

@section('title', 'Upload document')

@section('breadcrumb')
    <a href="{{ route('documents.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Documents</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Upload</span>
@endsection

@section('content')
    <div class="page-form">
        <h1 class="font-display font-bold text-3xl tracking-tight mb-6">Upload document</h1>
        <livewire:documents.upload />
    </div>
@endsection
