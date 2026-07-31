@extends('layouts.app')

@section('title', 'New post')

@section('breadcrumb')
    <a href="{{ route('news.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">News</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Create</span>
@endsection

@section('content')
    <div class="page-form-wide">
        <h1 class="font-display font-bold text-3xl tracking-tight mb-6">Create post</h1>
        <livewire:news.composer />
    </div>
@endsection
