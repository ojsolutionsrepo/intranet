@extends('layouts.app')

@section('title', 'New post')

@section('breadcrumb')
    <a href="{{ route('news.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">News</a>
    <span class="mx-2 text-[var(--ink-400)]">/</span>
    <span class="text-[var(--ink-900)] font-medium">Create</span>
@endsection

@section('content')
    <div class="page-form-wide">
        <h1 class="font-display font-bold text-3xl tracking-tight mb-6">Create post</h1>

        @if (session('status'))
            <p class="badge badge-ok mb-4">{{ session('status') }}</p>
        @endif

        {{-- Classic multipart: Livewire file uploads need PHP tmpfile()/fileinfo (often disabled on shared hosts). --}}
        <form method="POST" action="{{ route('news.store') }}" enctype="multipart/form-data" class="card p-5 space-y-4"
              x-data="ojRichEditor"
              x-init="html = @js(old('body_html', ''))"
              data-inline-upload="{{ route('news.inline-image') }}">
            @csrf

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="news-title">Title</label>
                <input id="news-title" type="text" name="title" value="{{ old('title') }}" class="input" required maxlength="200">
                @error('title') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="news-summary">Summary</label>
                <input id="news-summary" type="text" name="summary" value="{{ old('summary') }}" class="input" maxlength="500">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Body</label>
                <input type="hidden" name="body_html" :value="html">
                <div class="rich-editor">
                    <div class="rich-editor-toolbar" role="toolbar" aria-label="Formatting">
                        <button type="button" class="rich-editor-btn" title="Bold" @click="cmd('bold')"><strong>B</strong></button>
                        <button type="button" class="rich-editor-btn" title="Italic" @click="cmd('italic')"><em>I</em></button>
                        <button type="button" class="rich-editor-btn" title="Underline" @click="cmd('underline')"><span style="text-decoration:underline">U</span></button>
                        <span class="rich-editor-sep" aria-hidden="true"></span>
                        <button type="button" class="rich-editor-btn" title="Heading" @click="cmd('formatBlock', 'h3')">H</button>
                        <button type="button" class="rich-editor-btn" title="Bullet list" @click="cmd('insertUnorderedList')">• List</button>
                        <button type="button" class="rich-editor-btn" title="Numbered list" @click="cmd('insertOrderedList')">1. List</button>
                        <button type="button" class="rich-editor-btn" title="Link" @click="link()">Link</button>
                        <span class="rich-editor-sep" aria-hidden="true"></span>
                        <label class="rich-editor-btn rich-editor-btn-file" title="Insert image">
                            Image
                            <input type="file" class="sr-only" accept="image/*" @change="uploadInline($event)">
                        </label>
                    </div>
                    <div
                        x-ref="editor"
                        class="rich-editor-surface input"
                        contenteditable="true"
                        role="textbox"
                        aria-multiline="true"
                        aria-label="Post body"
                        data-placeholder="Write your announcement…"
                        @input="sync()"
                        @blur="sync()"
                    ></div>
                </div>
                <p class="note text-xs mt-1" x-show="uploadingInline" x-cloak>Uploading image…</p>
                @error('body_html') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                <p class="note text-xs mt-1">Formatted text is sanitized on save. Use Attachments below for documents and extra files.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="news-attachments">Attachments</label>
                <input id="news-attachments" type="file" name="attachments[]" class="input" multiple
                       accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.ppt,.pptx,image/*,application/pdf">
                <p class="note text-xs mt-1">Images, PDF, Office docs · up to 10 files · 10&nbsp;MB each</p>
                @error('attachments') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                @error('attachments.*') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="news-category">Category</label>
                    <input id="news-category" type="text" name="category" value="{{ old('category', 'General') }}" class="input" required maxlength="80">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="news-status">Status</label>
                    <select id="news-status" name="status" class="select">
                        <option value="draft" @selected(old('status', 'draft') === 'draft')>Draft</option>
                        <option value="in_review" @selected(old('status') === 'in_review')>In review</option>
                        <option value="published" @selected(old('status') === 'published')>Published</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Audience departments (empty = company-wide)</label>
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
                    <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned'))> Pin
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_alert" value="1" @checked(old('is_alert'))> Alert banner
                </label>
            </div>

            <button type="submit" class="btn btn-primary">Save post</button>
        </form>
    </div>
@endsection
