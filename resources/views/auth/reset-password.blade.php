@extends('layouts.guest')

@section('title', 'Set new password')

@section('content')
    <h1 class="font-display font-semibold text-xl mb-1">Choose a new password</h1>
    <p class="note mb-5">Enter your email and a new password.</p>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div>
            <label for="email" class="block text-[12.5px] font-semibold text-[var(--ink-700)] mb-1.5">Email</label>
            <input id="email" class="input" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus>
        </div>
        <div>
            <label for="password" class="block text-[12.5px] font-semibold text-[var(--ink-700)] mb-1.5">Password</label>
            <input id="password" class="input" type="password" name="password" required autocomplete="new-password">
        </div>
        <div>
            <label for="password_confirmation" class="block text-[12.5px] font-semibold text-[var(--ink-700)] mb-1.5">Confirm password</label>
            <input id="password_confirmation" class="input" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary w-full justify-center">Reset password</button>
    </form>
@endsection
