<div class="card p-5 h-full">
    <h3 class="font-display font-semibold text-base mb-3">Quick Links</h3>
    <p class="note mb-3">Comms, platform SSO, and intranet shortcuts.</p>
    @if ($error)
        <p class="note">{{ $error }}</p>
    @elseif ($grouped->isEmpty())
        <p class="note">No links configured yet.</p>
    @else
        @foreach (['comms' => 'Comms', 'platform' => 'Platforms / SSO', 'internal' => 'Intranet'] as $key => $heading)
            @if ($grouped->has($key) && $grouped[$key]->isNotEmpty())
                <p class="text-[11px] uppercase tracking-wider text-[var(--ink-500)] mb-2 mt-3 first:mt-0">{{ $heading }}</p>
                <div class="flex flex-wrap gap-2 mb-1">
                    @foreach ($grouped[$key] as $link)
                        <a href="{{ $link['href'] }}"
                           class="btn btn-ghost btn-sm"
                           @if ($link['opens_external']) target="_blank" rel="noopener" @endif
                           @if ($link['description']) title="{{ $link['description'] }}" @endif>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        @endforeach
        {{-- Any other categories --}}
        @foreach ($grouped as $key => $items)
            @continue(in_array($key, ['comms', 'platform', 'internal'], true))
            <p class="text-[11px] uppercase tracking-wider text-[var(--ink-500)] mb-2 mt-3">{{ ucfirst($key) }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($items as $link)
                    <a href="{{ $link['href'] }}" class="btn btn-ghost btn-sm" @if ($link['opens_external']) target="_blank" rel="noopener" @endif>
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        @endforeach
    @endif
</div>
