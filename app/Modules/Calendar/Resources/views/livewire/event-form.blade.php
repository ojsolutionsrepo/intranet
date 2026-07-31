<div>
    <form wire:submit="save" class="card p-5 space-y-4">
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Title</label>
            <input type="text" wire:model="title" class="input">
            @error('title') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Description</label>
            <textarea wire:model="description" rows="3" class="input"></textarea>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Category</label>
                <select wire:model="category" class="select">
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Location</label>
                <input type="text" wire:model="location" class="input">
            </div>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Starts</label>
                <input type="datetime-local" wire:model="starts_at" class="input">
                @error('starts_at') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Ends</label>
                <input type="datetime-local" wire:model="ends_at" class="input">
                @error('ends_at') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" wire:model="all_day"> All day</label>
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
        <button type="submit" class="btn btn-primary">Save event</button>
    </form>
</div>
