@extends('layouts.app')

@section('title', 'Subject-access export')

@section('breadcrumb')
    <a href="{{ route('admin.index') }}" class="hover:text-[var(--ink-900)]">Administration</a>
    <span class="mx-1.5 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Subject access</span>
@endsection

@section('content')
    <h1 class="font-display font-bold text-3xl tracking-tight mb-2">Subject-access export</h1>
    <p class="note mb-6">GDPR pack (JSON inside ZIP) for a staff member (UR-ADM-07).</p>

    <form method="POST" action="{{ route('admin.compliance.subject-access.export') }}" class="card p-5 max-w-lg space-y-4">
        @csrf
        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5">User</label>
            <select name="user_id" class="input" required>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Download export</button>
    </form>

    <form method="GET" action="{{ route('admin.compliance.audit-export') }}" class="card p-5 max-w-lg space-y-4 mt-6">
        <h2 class="font-display font-semibold text-lg">Audit log export</h2>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-[12.5px] font-semibold mb-1.5">From</label>
                <input type="date" name="from" class="input">
            </div>
            <div>
                <label class="block text-[12.5px] font-semibold mb-1.5">To</label>
                <input type="date" name="to" class="input">
            </div>
        </div>
        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5">Action filter</label>
            <input type="text" name="action" class="input" placeholder="login">
        </div>
        <button type="submit" class="btn btn-secondary">Export CSV</button>
    </form>
@endsection
