@extends('layouts.install')

@section('title', 'Admin account')

@section('content')
    <h1 class="font-display font-semibold text-xl mb-1">Admin account</h1>
    <p class="note mb-5">This user gets the Admin role and can manage the intranet.</p>

    <form method="POST" action="{{ route('install.admin.store') }}">
        @csrf
        <div class="field">
            <label for="site_name">Site name</label>
            <input id="site_name" class="input" type="text" name="site_name" value="{{ old('site_name', 'OJ Intranet') }}" required>
        </div>
        <div class="field">
            <label for="name">Full name</label>
            <input id="name" class="input" type="text" name="name" value="{{ old('name') }}" required autofocus>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" class="input" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" class="input" type="password" name="password" required minlength="8" autocomplete="new-password">
        </div>
        <div class="field">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" class="input" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
        </div>
        <div class="field" style="display: flex; gap: 10px; align-items: flex-start">
            <input id="seed_demo" type="checkbox" name="seed_demo" value="1" @checked(old('seed_demo')) style="margin-top: 3px">
            <label for="seed_demo" style="font-weight: 500; margin: 0">
                Load demo data
                <span class="note" style="display: block; font-weight: 400; margin-top: 4px">
                    Departments, sample news/documents, calendar, projects, and <span class="font-mono">staff@oj.local</span> / <span class="font-mono">password</span>. Safe for local or staging — skip on production.
                </span>
            </label>
        </div>
        <button type="submit" class="btn btn-primary">Finish installation</button>
    </form>
@endsection
