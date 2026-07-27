@extends('layouts.guest')

@section('title', 'Two-factor authentication')

@section('content')
    <h1 class="font-display font-semibold text-xl mb-1">Authenticator code</h1>
    <p class="note mb-5">Enter the code from your authenticator app to continue.</p>

    <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-4">
        @csrf
        <div>
            <label for="code" class="block text-[12.5px] font-semibold text-[var(--ink-700)] mb-1.5">Authentication code</label>
            <input id="code" class="input font-mono" type="text" inputmode="numeric" name="code" autofocus autocomplete="one-time-code">
        </div>
        <div class="text-center text-sm text-[var(--ink-500)]">or</div>
        <div>
            <label for="recovery_code" class="block text-[12.5px] font-semibold text-[var(--ink-700)] mb-1.5">Recovery code</label>
            <input id="recovery_code" class="input font-mono" type="text" name="recovery_code" autocomplete="one-time-code">
        </div>
        <button type="submit" class="btn btn-primary w-full justify-center">Verify</button>
    </form>
@endsection
