<div class="card p-5 h-full">
    <h3 class="font-display font-semibold text-base mb-3">Announcements</h3>
    @if ($error)
        <p class="note text-[var(--err-600)]">{{ $error }}</p>
    @else
        <ul class="space-y-2">
            @forelse ($posts as $post)
                <li>
                    <a href="{{ route('news.show', $post) }}" class="text-sm font-medium hover:text-[var(--sig-600)]">{{ $post->title }}</a>
                    <div class="note">{{ optional($post->published_at)->diffForHumans() }}</div>
                </li>
            @empty
                <li class="note">No announcements.</li>
            @endforelse
        </ul>
    @endif
</div>
