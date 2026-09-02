<div>
    <form wire:submit="save" class="card p-5 space-y-4">
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Title</label>
            <input type="text" wire:model="title" class="input">
            @error('title') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Summary</label>
            <input type="text" wire:model="summary" class="input">
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Body</label>
            <div
                class="rich-editor"
                x-data="ojRichEditor"
                x-init="html = $wire.entangle('body_html')"
                @rich-editor-insert-image.window="insertImage(($event.detail && $event.detail.url) ? $event.detail.url : '')"
            >
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
                        <input type="file" class="sr-only" accept="image/*" wire:model="inlineImage">
                    </label>
                </div>
                <div wire:ignore>
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
            </div>
            <div wire:loading wire:target="inlineImage" class="note text-xs mt-1">Uploading image…</div>
            @error('body_html') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            @error('inlineImage') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            <p class="note text-xs mt-1">Formatted text is sanitized on save. Use Attachments below for documents and extra files.</p>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Attachments</label>
            <input type="file" class="input" wire:model="attachments" multiple
                   accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.ppt,.pptx,image/*,application/pdf">
            <p class="note text-xs mt-1">Images, PDF, Office docs · up to 10 files · 10&nbsp;MB each</p>
            <div wire:loading wire:target="attachments" class="note text-xs mt-1">Uploading attachments…</div>
            @error('attachments') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            @error('attachments.*') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror

            @if (count($attachments))
                <ul class="mt-3 space-y-2">
                    @foreach ($attachments as $index => $file)
                        <li class="flex items-center justify-between gap-3 rounded-md border border-[var(--line)] bg-[var(--paper-2)] px-3 py-2 text-sm">
                            <span class="truncate">{{ $file->getClientOriginalName() }}</span>
                            <button type="button" class="btn btn-ghost btn-sm" wire:click="removeAttachment({{ $index }})">Remove</button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Category</label>
                <input type="text" wire:model="category" class="input">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Status</label>
                <select wire:model="status" class="select">
                    <option value="draft">Draft</option>
                    <option value="in_review">In review</option>
                    <option value="published">Published</option>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Audience departments (empty = company-wide)</label>
            <div class="flex flex-wrap gap-3">
                @foreach ($departments as $department)
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" value="{{ $department->id }}" wire:model="department_ids">
                        {{ $department->name }}
                    </label>
                @endforeach
            </div>
        </div>
        <div class="flex flex-wrap gap-4">
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_pinned"> Pin</label>
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_alert"> Alert banner</label>
        </div>
        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Save post</span>
            <span wire:loading wire:target="save">Saving…</span>
        </button>
    </form>
</div>
