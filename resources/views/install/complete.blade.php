@extends('layouts.install')

@section('title', 'Installed')

@section('content')
    <h1 class="font-display font-semibold text-xl mb-1">You’re set</h1>
    <p class="font-voice text-lg text-[var(--ink-700)] mb-4">The intranet foundation is installed. Sign in with the admin account you just created.</p>

    @if (session('install.seeded_demo'))
        <div class="alert alert-info mb-4">
            Demo data was loaded. You can also sign in as <span class="font-mono text-xs">staff@oj.local</span> / <span class="font-mono text-xs">password</span>.
        </div>
    @endif

    <div class="alert alert-info mb-5">
        The installer is locked. To re-run it later, delete <span class="font-mono text-xs">storage/app/installed</span> (local/dev only).
    </div>
    <a href="{{ route('login') }}" class="btn btn-primary">Go to sign in</a>
@endsection
