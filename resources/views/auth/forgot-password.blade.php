@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
    <h1 class="font-display font-semibold text-xl mb-1">Reset password</h1>
    <p class="note mb-5">We will email a single-use link that expires in 60 minutes.</p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-[12.5px] font-semibold text-[var(--ink-700)] mb-1.5">Email</label>
            <input id="email" class="input" type="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary w-full justify-center">Send reset link</button>
        <div class="text-center">
            <a class="text-sm text-oj-500 hover:underline" href="{{ route('login') }}">Back to sign in</a>
        </div>
    </form>
@endsection
