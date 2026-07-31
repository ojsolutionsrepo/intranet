<div>
    <form wire:submit="save" class="card p-5 space-y-4">
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Name</label>
            <input type="text" wire:model="name" class="input">
            @error('name') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Email</label>
            <input type="email" wire:model="email" class="input">
            @error('email') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Password {{ $userId ? '(leave blank to keep)' : '' }}</label>
            <input type="password" wire:model="password" class="input" autocomplete="new-password">
            @error('password') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Role</label>
                <select wire:model="role" class="select">
                    @foreach ($roles as $roleName)
                        <option value="{{ $roleName }}">{{ $roleName }}</option>
                    @endforeach
                </select>
                @error('role') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Department</label>
                <select wire:model="department_id" class="select">
                    <option value="">Select…</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
                @error('department_id') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Job title</label>
                <input type="text" wire:model="job_title" class="input">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Manager</label>
                <select wire:model="manager_id" class="select">
                    <option value="">None</option>
                    @foreach ($managers as $manager)
                        @if ($manager->id !== $userId)
                            <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="is_active"> Active
        </label>
        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">Cancel</a>
        </div>
    </form>
</div>
