<div class="card p-5 h-full">
    <h3 class="font-display font-semibold text-base mb-3">Upcoming Events</h3>
    @if ($error)
        <p class="note text-[var(--err-600)]">{{ $error }}</p>
    @else
        <ul class="space-y-2">
            @forelse ($events as $event)
                <li>
                    <a href="{{ route('calendar.index', ['focus' => $event->starts_at->toDateString()]) }}" class="text-sm font-medium hover:text-[var(--sig-600)]">
                        <span class="inline-block w-2 h-2 rounded-full mr-1" style="background: {{ $event->colour }}"></span>
                        {{ $event->title }}
                    </a>
                    <div class="note">{{ $event->starts_at->format('j M, H:i') }}</div>
                </li>
            @empty
                <li class="note">No upcoming events.</li>
            @endforelse
        </ul>
    @endif
</div>
