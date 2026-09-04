@extends('layouts.app')

@section('title', 'Upload document')

@section('breadcrumb')
    <a href="{{ route('documents.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Documents</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Upload</span>
@endsection

@section('content')
    <div class="page-form">
        <h1 class="font-display font-bold text-3xl tracking-tight mb-6">Upload document</h1>

        @if (session('status'))
            <p class="badge badge-ok mb-4">{{ session('status') }}</p>
        @endif

        {{-- Classic multipart form: Livewire WithFileUploads needs PHP tmpfile()/fileinfo (often disabled on shared hosts). --}}
        <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="card p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="doc-title">Title</label>
                <input id="doc-title" type="text" name="title" value="{{ old('title') }}" class="input" required>
                @error('title') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="doc-category">Category</label>
                <select id="doc-category" name="category_id" class="select" required>
                    <option value="">Select…</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="doc-file">File</label>
                <input id="doc-file" type="file" name="file" class="input" required
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.md">
                <p class="note text-xs mt-1">PDF, Office, TXT, CSV, Markdown · max 20&nbsp;MB</p>
                @error('file') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="doc-visibility">Visibility</label>
                <select id="doc-visibility" name="visibility" class="select">
                    <option value="inherit" @selected(old('visibility', 'inherit') === 'inherit')>Inherit category</option>
                    <option value="all" @selected(old('visibility') === 'all')>All company</option>
                    <option value="department" @selected(old('visibility') === 'department')>Departments</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Audience departments</label>
                <div class="flex flex-wrap gap-3">
                    @foreach ($departments as $department)
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="department_ids[]" value="{{ $department->id }}"
                                   @checked(collect(old('department_ids', []))->contains($department->id))>
                            {{ $department->name }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_policy" value="1" @checked(old('is_policy'))> Is policy
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="mandatory_ack" value="1" @checked(old('mandatory_ack'))> Mandatory acknowledgement
                </label>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="doc-review">Review date</label>
                <input id="doc-review" type="date" name="review_at" value="{{ old('review_at') }}" class="input">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="doc-changelog">Changelog</label>
                <input id="doc-changelog" type="text" name="changelog" value="{{ old('changelog', 'Initial upload') }}" class="input" maxlength="500">
            </div>
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>
    </div>
@endsection
