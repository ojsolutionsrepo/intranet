<div>
    @foreach ($alerts as $alert)
        <div class="alert alert-warn mb-4">
            <strong>{{ $alert->title }}</strong>
            <span class="note"> — {{ $alert->summary }}</span>
            <a href="{{ route('news.show', $alert) }}" class="btn btn-ghost btn-sm ml-2">Read</a>
        </div>
    @endforeach

    <div class="space-y-4">
        @forelse ($posts as $post)
            <a href="{{ route('news.show', $post) }}" class="card p-5 block hover:border-[var(--sig-500)]">
                <div class="flex flex-wrap gap-2 mb-2">
                    @if ($post->is_pinned)
                        <span class="badge badge-warn">Pinned</span>
                    @endif
                    <span class="badge badge-info">{{ $post->category }}</span>
                </div>
                <h2 class="font-display font-semibold text-xl mb-1">{{ $post->title }}</h2>
                <p class="text-sm text-[var(--ink-700)] mb-2">{{ $post->summary }}</p>
                <p class="note">{{ $post->author?->name }} · {{ optional($post->published_at)->diffForHumans() }}</p>
            </a>
        @empty
            <div class="card p-6"><p class="note">No published posts for your audience yet.</p></div>
        @endforelse
    </div>

    <div class="mt-5">{{ $posts->links() }}</div>
</div>
