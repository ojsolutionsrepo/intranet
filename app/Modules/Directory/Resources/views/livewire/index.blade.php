<div>
    <div class="card p-4 mb-5">
        <div class="grid gap-3 md:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Search</label>
                <input type="search" wire:model.live.debounce.300ms="q" class="input" placeholder="Name, email, location…">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Department</label>
                <select wire:model.live="department_id" class="select">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Role</label>
                <select wire:model.live="role" class="select">
                    <option value="">All roles</option>
                    @foreach ($roles as $roleName)
                        <option value="{{ $roleName }}">{{ $roleName }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Expertise</label>
                <select wire:model.live="expertise" class="select">
                    <option value="">Any expertise</option>
                    @foreach ($expertiseTags as $tag)
                        <option value="{{ $tag }}">{{ $tag }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-3">
            <button type="button" wire:click="clearFilters" class="btn btn-ghost btn-sm">Clear filters</button>
            <div class="flex gap-2">
                <button type="button" wire:click="$set('viewMode', 'cards')" class="btn btn-sm {{ $viewMode === 'cards' ? 'btn-secondary' : 'btn-ghost' }}">Cards</button>
                <button type="button" wire:click="$set('viewMode', 'list')" class="btn btn-sm {{ $viewMode === 'list' ? 'btn-secondary' : 'btn-ghost' }}">List</button>
            </div>
        </div>
    </div>

    <p class="note mb-4">{{ $people->total() }} people</p>

    @if ($viewMode === 'cards')
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($people as $person)
                <a href="{{ route('directory.show', $person) }}" class="card p-4 hover:border-[var(--sig-500)] transition-colors block">
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 rounded-full bg-oj-800 text-white grid place-items-center font-display font-semibold overflow-hidden shrink-0">
                            @if ($person->profile?->thumbUrl())
                                <img src="{{ $person->profile->thumbUrl() }}" alt="" class="w-full h-full object-cover">
                            @else
                                {{ strtoupper(substr($person->name, 0, 1)) }}
                            @endif
                        </div>
                        <div class="min-w-0">
                            <div class="font-display font-semibold text-[15px] truncate">{{ $person->name }}</div>
                            <div class="text-[13px] text-[var(--ink-700)] truncate">{{ $person->jobTitle() ?: '—' }}</div>
                            <div class="text-[12px] text-[var(--ink-500)] mt-1 truncate">
                                {{ $person->primaryDepartment()?->name ?: 'No department' }}
                                @if ($person->profile?->extension)
                                    · Ext {{ $person->profile->extension }}
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach (array_slice($person->expertiseTags(), 0, 3) as $tag)
                                    <span class="badge badge-info">{{ $tag }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="card p-6 sm:col-span-2 lg:col-span-3">
                    <p class="note">No people match these filters.</p>
                </div>
            @endforelse
        </div>
    @else
        <div class="card overflow-hidden">
            <table class="w-full text-[13.5px]">
                <thead>
                <tr class="text-left text-[11px] uppercase tracking-wider text-[var(--ink-500)] border-b-2 border-[var(--line-strong)]">
                    <th class="py-2 px-3">Name</th>
                    <th class="py-2 px-3">Department</th>
                    <th class="py-2 px-3">Title</th>
                    <th class="py-2 px-3">Ext</th>
                    <th class="py-2 px-3">Role</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($people as $person)
                    <tr class="border-b border-[var(--line)] hover:bg-[var(--paper-2)]">
                        <td class="py-3 px-3">
                            <a href="{{ route('directory.show', $person) }}" class="font-medium text-[var(--ink-900)] hover:text-[var(--sig-600)]">{{ $person->name }}</a>
                        </td>
                        <td class="py-3 px-3">{{ $person->primaryDepartment()?->name ?: '—' }}</td>
                        <td class="py-3 px-3">{{ $person->jobTitle() ?: '—' }}</td>
                        <td class="py-3 px-3 font-mono text-xs">{{ $person->profile?->extension ?: '—' }}</td>
                        <td class="py-3 px-3">{{ $person->roles->pluck('name')->join(', ') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 px-3 note">No people match these filters.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <div class="mt-5">
        {{ $people->links() }}
    </div>
</div>
