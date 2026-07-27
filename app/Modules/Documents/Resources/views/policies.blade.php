@extends('layouts.app')

@section('title', 'Policy hub')

@section('breadcrumb')
    <span class="text-[var(--ink-900)] font-medium">Policies</span>
@endsection

@section('content')
    <div class="eyebrow font-mono text-[11px] tracking-widest uppercase text-signal-500 mb-2">Compliance</div>
    <h1 class="font-display font-bold text-4xl tracking-tight mb-2" style="letter-spacing: -0.03em">Policy hub</h1>
    <p class="font-voice text-lg text-[var(--ink-700)] max-w-[62ch] mb-6">Mandatory policies, acknowledgements, and review status.</p>
    <livewire:documents.policy-hub />
@endsection
