<div>
    <form wire:submit="save" class="card p-5 space-y-4">
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Title</label>
            <input type="text" wire:model="title" class="input">
            @error('title') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Category</label>
            <select wire:model="category_id" class="select">
                <option value="">Select…</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">File</label>
            <input type="file" wire:model="file" class="input">
            @error('file') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Visibility</label>
            <select wire:model="visibility" class="select">
                <option value="inherit">Inherit category</option>
                <option value="all">All company</option>
                <option value="department">Departments</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Audience departments</label>
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
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_policy"> Is policy</label>
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="mandatory_ack"> Mandatory acknowledgement</label>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Review date</label>
            <input type="date" wire:model="review_at" class="input">
        </div>
        <button type="submit" class="btn btn-primary">Upload</button>
    </form>
</div>
