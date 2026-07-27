<div>
    <div class="mb-4 max-w-sm">
        <input type="search" wire:model.live.debounce.300ms="q" class="input" placeholder="Search users…">
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full text-[13.5px]">
            <thead>
            <tr class="text-left text-[11px] uppercase tracking-wider text-[var(--ink-500)] border-b-2 border-[var(--line-strong)]">
                <th class="py-2 px-3">Name</th>
                <th class="py-2 px-3">Email</th>
                <th class="py-2 px-3">Role</th>
                <th class="py-2 px-3">Department</th>
                <th class="py-2 px-3">Status</th>
                <th class="py-2 px-3"></th>
            </tr>
            </thead>
            <tbody>
            @foreach ($users as $user)
                <tr class="border-b border-[var(--line)]">
                    <td class="py-3 px-3 font-medium">{{ $user->name }}</td>
                    <td class="py-3 px-3">{{ $user->email }}</td>
                    <td class="py-3 px-3">{{ $user->roles->pluck('name')->join(', ') }}</td>
                    <td class="py-3 px-3">{{ $user->primaryDepartment()?->name ?: '—' }}</td>
                    <td class="py-3 px-3">
                        @if ($user->is_active)
                            <span class="badge badge-ok">Active</span>
                        @else
                            <span class="badge badge-err">Inactive</span>
                        @endif
                    </td>
                    <td class="py-3 px-3 text-right whitespace-nowrap">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-ghost btn-sm">Edit</a>
                        @if ($user->is_active)
                            <button type="button" wire:click="deactivate({{ $user->id }})" class="btn btn-ghost btn-sm" wire:confirm="Deactivate {{ $user->name }}?">Deactivate</button>
                        @else
                            <button type="button" wire:click="reactivate({{ $user->id }})" class="btn btn-ghost btn-sm">Reactivate</button>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
