@extends('layouts.install')

@section('title', 'Database')

@section('content')
    <h1 class="font-display font-semibold text-xl mb-1">Database</h1>
    <p class="note mb-5">SQLite is simplest on XAMPP. Use MySQL if you already run it in the control panel.</p>

    @php
        $defaultConnection = old('connection', $hasSqlite ? 'sqlite' : 'mysql');
    @endphp

    <form method="POST" action="{{ route('install.database.store') }}" class="space-y-4" id="install-db-form">
        @csrf

        <div class="field">
            <label for="connection">Connection</label>
            <select id="connection" name="connection" class="select">
                @if ($hasSqlite)
                    <option value="sqlite" @selected($defaultConnection === 'sqlite')>SQLite (file)</option>
                @endif
                @if ($hasMysql)
                    <option value="mysql" @selected($defaultConnection === 'mysql')>MySQL</option>
                @endif
            </select>
        </div>

        <div id="mysql-fields" @if ($defaultConnection !== 'mysql') hidden @endif>
            <div class="field">
                <label for="host">Host</label>
                <input id="host" class="input" type="text" name="host" value="{{ old('host', '127.0.0.1') }}" autocomplete="off">
            </div>
            <div class="field">
                <label for="port">Port</label>
                <input id="port" class="input" type="text" name="port" value="{{ old('port', '3306') }}" autocomplete="off">
            </div>
            <div class="field">
                <label for="database">Database name</label>
                <input id="database" class="input" type="text" name="database" value="{{ old('database', 'oj_intranet') }}" autocomplete="off">
            </div>
            <div class="field">
                <label for="username">Username</label>
                <input id="username" class="input" type="text" name="username" value="{{ old('username', 'root') }}" autocomplete="username">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" class="input" type="password" name="password" value="{{ old('password') }}" autocomplete="current-password">
            </div>
            <p class="note">If the database does not exist yet, the installer will try to create it (XAMPP root usually can).</p>
        </div>

        <div id="sqlite-hint" class="alert alert-info" @if ($defaultConnection !== 'sqlite') hidden @endif>
            Will use <span class="font-mono text-xs">database/database.sqlite</span> and create it if needed.
        </div>

        <button type="submit" class="btn btn-primary">Save &amp; migrate</button>
    </form>

    <script>
        (function () {
            var select = document.getElementById('connection');
            var mysql = document.getElementById('mysql-fields');
            var sqlite = document.getElementById('sqlite-hint');
            if (!select || !mysql || !sqlite) return;

            function sync() {
                var isMysql = select.value === 'mysql';
                mysql.hidden = !isMysql;
                sqlite.hidden = isMysql;
            }

            select.addEventListener('change', sync);
            sync();
        })();
    </script>
@endsection
