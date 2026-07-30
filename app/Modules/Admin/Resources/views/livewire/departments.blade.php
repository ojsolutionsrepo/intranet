<div>
    @if (session('status'))
        <div class="alert alert-info mb-4">{{ session('status') }}</div>
    @endif

    <form wire:submit="save" class="card p-5 mb-6 space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-display font-semibold text-base">
                {{ $editingId ? 'Edit department' : 'Add department' }}
            </h2>
            @if ($editingId)
                <button type="button" class="btn btn-ghost btn-sm" wire:click="cancelEdit">Cancel</button>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-[12.5px] font-semibold mb-1.5" for="dept-name">Name</label>
                <input id="dept-name" type="text" class="input" wire:model="name" placeholder="Engineering" required>
                @error('name') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[12.5px] font-semibold mb-1.5" for="dept-order">Sort order</label>
                <input id="dept-order" type="number" min="0" class="input" wire:model="order">
                @error('order') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[12.5px] font-semibold mb-1.5" for="dept-parent">Parent department</label>
                <select id="dept-parent" class="select" wire:model="parent_id">
                    <option value="">None (top-level)</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                    @endforeach
                </select>
                @error('parent_id') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[12.5px] font-semibold mb-1.5" for="dept-lead">Department lead</label>
                <select id="dept-lead" class="select" wire:model="lead_user_id">
                    <option value="">None</option>
                    @foreach ($leads as $lead)
                        <option value="{{ $lead->id }}">{{ $lead->name }} ({{ $lead->email }})</option>
                    @endforeach
                </select>
                @error('lead_user_id') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="block text-[12.5px] font-semibold mb-1.5" for="dept-description">Description</label>
                <textarea id="dept-description" class="input" rows="3" wire:model="description" placeholder="Optional"></textarea>
                @error('description') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn btn-primary btn-sm">
                {{ $editingId ? 'Save changes' : 'Add department' }}
            </button>
        </div>
    </form>

    <div class="card overflow-x-auto">
        <table class="w-full text-[13.5px]">
            <thead>
            <tr class="text-left text-[11px] uppercase tracking-wider text-[var(--ink-500)] border-b-2 border-[var(--line-strong)]">
                <th class="py-2 px-3">Name</th>
                <th class="py-2 px-3">Parent</th>
                <th class="py-2 px-3">Lead</th>
                <th class="py-2 px-3">People</th>
                <th class="py-2 px-3">Order</th>
                <th class="py-2 px-3"></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($departments as $department)
                <tr class="border-b border-[var(--line)]" wire:key="dept-{{ $department->id }}">
                    <td class="py-3 px-3">
                        <div class="font-medium">{{ $department->name }}</div>
                        @if ($department->description)
                            <div class="note text-xs mt-0.5 line-clamp-1">{{ $department->description }}</div>
                        @endif
                    </td>
                    <td class="py-3 px-3">{{ $department->parent?->name ?: '—' }}</td>
                    <td class="py-3 px-3">{{ $department->lead?->name ?: '—' }}</td>
                    <td class="py-3 px-3 font-mono text-xs">{{ $department->users_count }}</td>
                    <td class="py-3 px-3 font-mono text-xs">{{ $department->order }}</td>
                    <td class="py-3 px-3 text-right whitespace-nowrap">
                        <button type="button" class="btn btn-ghost btn-sm" wire:click="edit({{ $department->id }})">Edit</button>
                        <button type="button"
                                class="btn btn-ghost btn-sm"
                                wire:click="delete({{ $department->id }})"
                                wire:confirm="Remove {{ $department->name }}?">
                            Remove
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-6 px-3 note">No departments yet. Add one above.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
