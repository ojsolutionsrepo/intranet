@extends('layouts.install')

@section('title', 'Database')

@section('content')
    <h1 class="font-display font-semibold text-xl mb-1">Database</h1>
    <p class="note mb-5">SQLite is simplest on XAMPP. Use MySQL if you already run it in the control panel.</p>

    <form method="POST" action="{{ route('install.database.store') }}" class="space-y-4" x-data="{ connection: '{{ old('connection', $hasSqlite ? 'sqlite' : 'mysql') }}' }">
        @csrf

        <div class="field">
            <label for="connection">Connection</label>
            <select id="connection" name="connection" class="select" x-model="connection">
                @if ($hasSqlite)
                    <option value="sqlite">SQLite (file)</option>
                @endif
                @if ($hasMysql)
                    <option value="mysql">MySQL</option>
                @endif
            </select>
        </div>

        <div x-show="connection === 'mysql'" x-cloak>
            <div class="field">
                <label for="host">Host</label>
                <input id="host" class="input" type="text" name="host" value="{{ old('host', '127.0.0.1') }}">
            </div>
            <div class="field">
                <label for="port">Port</label>
                <input id="port" class="input" type="text" name="port" value="{{ old('port', '3306') }}">
            </div>
            <div class="field">
                <label for="database">Database name</label>
                <input id="database" class="input" type="text" name="database" value="{{ old('database', 'oj_intranet') }}">
            </div>
            <div class="field">
                <label for="username">Username</label>
                <input id="username" class="input" type="text" name="username" value="{{ old('username', 'root') }}">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" class="input" type="password" name="password" value="{{ old('password') }}">
            </div>
            <p class="note">Create an empty MySQL database first (e.g. in phpMyAdmin).</p>
        </div>

        <div x-show="connection === 'sqlite'" class="alert alert-info">
            Will use <span class="font-mono text-xs">database/database.sqlite</span> and create it if needed.
        </div>

        <button type="submit" class="btn btn-primary">Save &amp; migrate</button>
    </form>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endsection
