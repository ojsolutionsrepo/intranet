@extends('layouts.app')

@section('title', 'Import staff')

@section('breadcrumb')
    <a href="{{ route('directory.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Directory</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Import</span>
@endsection

@section('content')
    <div class="page-form-wide">
        <h1 class="font-display font-bold text-3xl tracking-tight mb-2">Import staff</h1>
        <p class="note mb-6">Upload CSV or XLSX, preview validation errors, then commit valid rows only.</p>
        <livewire:directory.staff-import />
    </div>
@endsection
