<div>
    <div class="card p-4 mb-5 grid gap-3 md:grid-cols-3">
        <div class="md:col-span-1">
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Query</label>
            <input type="search" wire:model.live.debounce.250ms="q" class="input" placeholder="Search…" autofocus>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Type</label>
            <select wire:model.live="type" class="select">
                <option value="">All types</option>
                @foreach ($types as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Department</label>
            <select wire:model.live="department_id" class="select">
                <option value="">Any</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($q !== '')
        <p class="note mb-4">{{ $hits->count() }} results in {{ number_format($tookMs, 1) }} ms</p>
    @endif

    @if (! empty($facets['type']))
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach ($facets['type'] as $facetType => $count)
                <button type="button" wire:click="$set('type', '{{ $facetType }}')" class="badge badge-info">{{ $facetType }} · {{ $count }}</button>
            @endforeach
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($hits as $hit)
            <a href="{{ $hit['url'] }}" class="card p-4 block hover:border-[var(--sig-500)]">
                <div class="flex items-center gap-2 mb-1">
                    <span class="badge badge-info">{{ $hit['type'] }}</span>
                    @if (! empty($hit['date']))
                        <span class="note">{{ $hit['date'] }}</span>
                    @endif
                </div>
                <div class="font-display font-semibold">{{ $hit['title'] }}</div>
                @if (! empty($hit['subtitle']))
                    <div class="note">{{ $hit['subtitle'] }}</div>
                @endif
            </a>
        @empty
            @if ($q !== '')
                <div class="card p-6"><p class="note">No results for “{{ $q }}”.</p></div>
            @endif
        @endforelse
    </div>
</div>
