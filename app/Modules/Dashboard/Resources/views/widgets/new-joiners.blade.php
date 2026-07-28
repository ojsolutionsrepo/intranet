<div class="card p-5 h-full">
    <h3 class="font-display font-semibold text-base mb-3">New joiners</h3>
    @if ($error)
        <p class="note text-[var(--err-600)]">{{ $error }}</p>
    @else
        <ul class="space-y-2">
            @forelse ($joiners as $person)
                <li>
                    <a href="{{ route('directory.show', $person) }}" class="text-sm font-medium hover:text-[var(--sig-600)]">{{ $person->name }}</a>
                    <div class="note">Started {{ optional($person->profile?->start_date)->format('j M Y') }}</div>
                </li>
            @empty
                <li class="note">No recent joiners.</li>
            @endforelse
        </ul>
    @endif
</div>
