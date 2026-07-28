<div>
    <div class="card p-4 mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="previous" class="btn btn-ghost btn-sm">‹</button>
            <button type="button" wire:click="next" class="btn btn-ghost btn-sm">›</button>
            <span class="font-display font-semibold px-2">{{ $focusDate->format('F Y') }}</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="$set('viewMode', 'month')" class="btn btn-sm {{ $viewMode === 'month' ? 'btn-secondary' : 'btn-ghost' }}">Month</button>
            <button type="button" wire:click="$set('viewMode', 'week')" class="btn btn-sm {{ $viewMode === 'week' ? 'btn-secondary' : 'btn-ghost' }}">Week</button>
            <button type="button" wire:click="$set('viewMode', 'list')" class="btn btn-sm {{ $viewMode === 'list' ? 'btn-secondary' : 'btn-ghost' }}">List</button>
            <select wire:model.live="category" class="select w-auto">
                <option value="">All categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($viewMode === 'list')
        <div class="card divide-y divide-[var(--line)]">
            @forelse ($events as $event)
                <div class="p-4 flex gap-3">
                    <span class="mt-1 w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $event->colour }}"></span>
                    <div>
                        <div class="font-display font-semibold">{{ $event->title }}</div>
                        <div class="note">{{ $event->starts_at->format('D j M Y H:i') }} – {{ $event->ends_at->format('H:i') }} · {{ $event->category }}</div>
                        @if ($event->location)
                            <div class="note">{{ $event->location }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-6 note">No events in this range.</div>
            @endforelse
        </div>
    @else
        @php
            $days = [];
            $cursor = $from->copy();
            while ($cursor <= $to) {
                $days[] = $cursor->copy();
                $cursor->addDay();
            }
            $byDay = $events->groupBy(fn ($e) => $e->starts_at->toDateString());
        @endphp
        <div class="card overflow-hidden">
            <div class="grid grid-cols-7 text-[11px] uppercase tracking-wider text-[var(--ink-500)] border-b border-[var(--line-strong)]">
                @foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow)
                    <div class="px-2 py-2">{{ $dow }}</div>
                @endforeach
            </div>
            <div class="grid grid-cols-7">
                @foreach ($days as $day)
                    <div class="min-h-[96px] border-b border-r border-[var(--line)] p-2 {{ $day->isSameMonth($focusDate) ? '' : 'opacity-40' }} {{ $day->isToday() ? 'bg-[var(--paper-2)]' : '' }}">
                        <div class="text-xs font-mono mb-1">{{ $day->day }}</div>
                        @foreach (($byDay[$day->toDateString()] ?? collect()) as $event)
                            <div class="text-[11px] truncate mb-0.5 px-1 rounded" style="background: {{ $event->colour }}22; color: {{ $event->colour }}">
                                {{ $event->title }}
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="flex flex-wrap gap-3 mt-4">
        @foreach ($colours as $name => $hex)
            <span class="inline-flex items-center gap-1.5 text-xs text-[var(--ink-700)]">
                <span class="w-2.5 h-2.5 rounded-full" style="background: {{ $hex }}"></span>{{ $name }}
            </span>
        @endforeach
    </div>
</div>
