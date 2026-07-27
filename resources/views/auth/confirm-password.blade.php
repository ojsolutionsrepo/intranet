@extends('layouts.guest')

@section('title', 'Confirm password')

@section('content')
    <h1 class="font-display font-semibold text-xl mb-1">Confirm password</h1>
    <p class="note mb-5">Please confirm your password before continuing.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf
        <div>
            <label for="password" class="block text-[12.5px] font-semibold text-[var(--ink-700)] mb-1.5">Password</label>
            <input id="password" class="input" type="password" name="password" required autofocus autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary w-full justify-center">Confirm</button>
    </form>
@endsection
