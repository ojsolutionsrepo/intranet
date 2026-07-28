<div class="card p-5 h-full">
    <h3 class="font-display font-semibold text-base mb-3">My Documents</h3>
    @if ($error)
        <p class="note text-[var(--err-600)]">{{ $error }}</p>
    @else
        <ul class="space-y-2">
            @forelse ($documents as $document)
                <li>
                    <a href="{{ route('documents.show', $document) }}" class="text-sm font-medium hover:text-[var(--sig-600)]">{{ $document->title }}</a>
                    <div class="note">v{{ $document->currentVersion?->version_number }}</div>
                </li>
            @empty
                <li class="note">No owned documents.</li>
            @endforelse
        </ul>
    @endif
</div>
