<div>
    <form wire:submit="save" class="card p-5 space-y-4 max-w-xl">
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Bio</label>
            <textarea wire:model="bio" rows="4" class="input"></textarea>
            @error('bio') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Phone</label>
                <input type="text" wire:model="phone" class="input">
                @error('phone') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Extension</label>
                <input type="text" wire:model="extension" class="input">
                @error('extension') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Location</label>
            <input type="text" wire:model="location" class="input">
            @error('location') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Expertise (comma-separated)</label>
            <input type="text" wire:model="expertiseInput" class="input" placeholder="Laravel, Mentoring, HRIS">
            @error('expertiseInput') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Photo</label>
            <input type="file" wire:model="photo" accept="image/*" class="input">
            <div wire:loading wire:target="photo" class="note mt-1">Uploading…</div>
            @error('photo') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            @if ($user?->profile?->thumbUrl())
                <img src="{{ $user->profile->thumbUrl() }}" alt="" class="w-16 h-16 rounded-full mt-3 object-cover">
            @endif
        </div>

        <div class="card p-3 bg-[var(--paper-2)]">
            <p class="text-xs text-[var(--ink-500)] uppercase tracking-wider mb-1">Read-only</p>
            <p class="text-sm">{{ $user?->name }} · {{ $user?->email }}</p>
            <p class="text-sm">{{ $user?->jobTitle() ?: '—' }} · {{ $user?->primaryDepartment()?->name ?: '—' }}</p>
            <p class="text-sm">Roles: {{ $user?->getRoleNames()->join(', ') }}</p>
        </div>

        <button type="submit" class="btn btn-primary">Save profile</button>
    </form>
</div>
