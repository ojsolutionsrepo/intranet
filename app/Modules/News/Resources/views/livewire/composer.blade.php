<div>
    <p class="note mb-4">Prefer <a class="underline" href="{{ route('news.create') }}">Create post</a> for attachments (classic upload works on restricted hosts).</p>
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
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Body (HTML)</label>
            <textarea wire:model="body_html" class="input min-h-[10rem]" rows="8"></textarea>
            @error('body_html') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
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
