@php
    $children = $people->where('manager_id', $person->id)->values();
    $isOpen = $expanded[$person->id] ?? false;
@endphp
<div class="mb-2" style="margin-left: {{ $depth * 1.25 }}rem">
    <div class="card p-3 flex items-center gap-3">
        @if ($children->isNotEmpty())
            <button type="button" wire:click="toggle({{ $person->id }})" class="btn btn-ghost btn-sm font-mono">
                {{ $isOpen ? '−' : '+' }}
            </button>
        @else
            <span class="w-8"></span>
        @endif
        <div>
            <a href="{{ route('directory.show', $person) }}" class="font-display font-semibold hover:text-[var(--sig-600)]">{{ $person->name }}</a>
            <div class="text-xs text-[var(--ink-500)]">{{ $person->jobTitle() ?: '—' }} · {{ $person->primaryDepartment()?->name ?: '—' }}</div>
        </div>
    </div>
    @if ($isOpen)
        @foreach ($children as $child)
            @include('directory::partials.org-node', ['person' => $child, 'people' => $people, 'depth' => $depth + 1])
        @endforeach
    @endif
</div>
