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
                </div>
                @if ($check['ok'])
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
