@extends('layouts.install')

@section('title', 'Installed')

@section('content')
    <h1 class="font-display font-semibold text-xl mb-1">You’re set</h1>
    <p class="font-voice text-lg text-[var(--ink-700)] mb-4">The intranet foundation is installed. Sign in with the admin account you just created.</p>
    <div class="alert alert-info mb-5">
        The installer is locked. To re-run it later, delete <span class="font-mono text-xs">storage/app/installed</span> (local/dev only).
    </div>
    <a href="{{ route('login') }}" class="btn btn-primary">Go to sign in</a>
@endsection
