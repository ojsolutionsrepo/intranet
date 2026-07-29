@extends('layouts.install')

@section('title', 'Requirements')

@section('content')
    <h1 class="font-display font-semibold text-xl mb-1">Server requirements</h1>
    <p class="note mb-5">These checks use the PHP that serves this page (XAMPP Apache PHP).</p>

    <div class="mb-5">
        @foreach ($checks as $check)
            <div class="check-row">
                <div>
                    <div class="font-medium">{{ $check['label'] }}</div>
                    <div class="note" style="font-size: 12px">{{ $check['detail'] }}</div>
                    @if (! empty($check['hint']) && ($check['id'] ?? '') === 'apache_alias' && str_contains($check['detail'] ?? '', 'fallback'))
                        <div class="alert alert-warn mt-2" style="font-size: 12px">
                            <div class="font-semibold mb-1">Optional — add Apache Alias</div>
                            <ol class="list-decimal pl-4 space-y-1 mb-2">
                                <li>Enable <span class="font-mono">rewrite_module</span> in <span class="font-mono">httpd.conf</span></li>
                                <li>Append this line to <span class="font-mono">httpd-vhosts.conf</span> (edit paths in <span class="font-mono">apache/alias.conf</span> if needed):</li>
                            </ol>
                            <pre class="font-mono text-[11px] p-2 overflow-x-auto" style="background: var(--paper-2); border-radius: 6px">{{ $check['hint'] }}</pre>
                            <div class="mt-2">Restart Apache, then refresh. You can continue without this — the installer already handles the fallback.</div>
                        </div>
                    @endif
                </div>
                @if (! empty($check['advisory']))
                    <span class="badge badge-ok">Info</span>
                @elseif ($check['ok'])
                    <span class="badge badge-ok">OK</span>
                @else
                    <span class="badge badge-err">Fail</span>
                @endif
            </div>
        @endforeach
    </div>

    @if ($passed)
        <form method="POST" action="{{ route('install.requirements.store') }}">
            @csrf
            <button type="submit" class="btn btn-primary">Continue to database</button>
        </form>
    @else
        <div class="alert alert-warn mb-4">Fix the failed items, then refresh this page.</div>
        <a href="{{ route('install.requirements') }}" class="btn btn-secondary">Re-check</a>
    @endif
@endsection
