@extends('layouts.app')

@section('title', 'Privacy notice')

@section('breadcrumb')
    <span class="text-[var(--ink-900)] font-medium">Privacy</span>
@endsection

@section('content')
    <div class="page-form-wide">
        <h1 class="font-display font-bold text-3xl tracking-tight mb-4">Privacy notice</h1>
        <div class="prose space-y-4 text-[13.5px] text-[var(--ink-700)]">
            <p>OJ Solutions processes staff personal data on this intranet to support directory, communications, documents, policies, projects, and security auditing.</p>
            <p>Legal basis: legitimate interests / employment contract. Contact: <a class="text-[var(--sig-500)]" href="mailto:{{ $contact }}">{{ $contact }}</a>.</p>
            <h2 class="font-display font-semibold text-lg text-[var(--ink-900)]">Retention</h2>
            <ul class="list-disc pl-5 space-y-1">
                <li>Audit logs: {{ $retention['audit_logs'] }} days</li>
                <li>Sessions: {{ $retention['sessions'] }} days</li>
                <li>Search zero-results: {{ $retention['search_zero_results'] }} days</li>
                <li>SSO one-time tokens: {{ $retention['sso_jtis'] }} days</li>
            </ul>
            <p>You may request a subject-access export via your administrator.</p>
        </div>
    </div>
@endsection
