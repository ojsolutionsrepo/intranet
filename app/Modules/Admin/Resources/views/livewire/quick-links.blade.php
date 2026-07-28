<div>
    <form wire:submit="save" class="grid gap-3 md:grid-cols-2 mb-6">
        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5">Label</label>
            <input type="text" class="input" wire:model="label" placeholder="Zenzap · General">
            @error('label') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5">URL</label>
            <input type="text" class="input" wire:model="url" placeholder="https://… or mailto:… or route:projects.index">
            @error('url') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5">Category</label>
            <select class="input" wire:model="category">
                <option value="comms">Comms (email, Zenzap…)</option>
                <option value="platform">Platform (SSO / tools)</option>
                <option value="internal">Internal route</option>
            </select>
        </div>
        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5">Description</label>
            <input type="text" class="input" wire:model="description" placeholder="Optional hint for staff">
        </div>
        <div class="md:col-span-2 flex items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="opens_external"> Opens in new tab
            </label>
            <button type="submit" class="btn btn-primary btn-sm">Add link</button>
        </div>
    </form>

    <table class="w-full text-[13.5px]">
        <thead>
        <tr class="text-left text-[11px] uppercase tracking-wider text-[var(--ink-500)] border-b-2 border-[var(--line-strong)]">
            <th class="py-2 px-3">Label</th>
            <th class="py-2 px-3">Category</th>
            <th class="py-2 px-3">URL</th>
            <th class="py-2 px-3">Active</th>
            <th class="py-2 px-3"></th>
        </tr>
        </thead>
        <tbody>
        @forelse ($links as $link)
            <tr class="border-b border-[var(--line)]" wire:key="ql-{{ $link->id }}">
                <td class="py-3 px-3 font-medium">{{ $link->label }}</td>
                <td class="py-3 px-3"><span class="badge badge-info">{{ $link->category }}</span></td>
                <td class="py-3 px-3 font-mono text-xs truncate max-w-[280px]">{{ $link->url }}</td>
                <td class="py-3 px-3">
                    <button type="button" class="badge badge-{{ $link->is_active ? 'ok' : 'err' }}" wire:click="toggle({{ $link->id }})">
                        {{ $link->is_active ? 'On' : 'Off' }}
                    </button>
                </td>
                <td class="py-3 px-3 text-right">
                    <button type="button" class="btn btn-ghost btn-sm" wire:click="delete({{ $link->id }})" wire:confirm="Remove this link?">Remove</button>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="py-4 px-3 note">No quick links yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
