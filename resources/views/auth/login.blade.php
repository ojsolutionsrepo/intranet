@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
    <h1 class="font-display font-semibold text-xl mb-1">Sign in</h1>
    <p class="note mb-5">Use your company email and password.</p>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-[12.5px] font-semibold text-[var(--ink-700)] mb-1.5">Email</label>
            <input id="email" class="input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>
        <div>
            <label for="password" class="block text-[12.5px] font-semibold text-[var(--ink-700)] mb-1.5">Password</label>
            <input id="password" class="input" type="password" name="password" required autocomplete="current-password">
        </div>
        <label class="flex items-center gap-2 text-sm text-[var(--ink-700)]">
            <input type="checkbox" name="remember"> Remember me
        </label>
        <button type="submit" class="btn btn-primary w-full justify-center">Sign in</button>
        @if (Route::has('password.request'))
            <div class="text-center">
                <a class="text-sm text-oj-500 hover:underline" href="{{ route('password.request') }}">Forgot password?</a>
            </div>
        @endif
    </form>
@endsection
