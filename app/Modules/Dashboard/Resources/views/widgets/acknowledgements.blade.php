<div class="card p-5 h-full">
    <h3 class="font-display font-semibold text-base mb-3">Outstanding acknowledgements</h3>
    @if ($error)
        <p class="note text-[var(--err-600)]">{{ $error }}</p>
    @else
        <ul class="space-y-2">
            @forelse ($items as $item)
                <li>
                    <a href="{{ route('policies.index') }}" class="text-sm font-medium hover:text-[var(--sig-600)]">{{ $item->title }}</a>
                    <div class="note">v{{ $item->currentVersion?->version_number }}</div>
                </li>
            @empty
                <li class="note">You're up to date.</li>
            @endforelse
        </ul>
    @endif
</div>
