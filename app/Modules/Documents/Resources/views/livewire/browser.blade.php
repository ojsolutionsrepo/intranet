<div>
    <div class="card p-4 mb-5 grid gap-3 md:grid-cols-2">
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Search titles</label>
            <input type="search" wire:model.live.debounce.300ms="q" class="input" placeholder="Find a document…">
        </div>
        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1">Category</label>
            <select wire:model.live="category_id" class="select">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="card overflow-hidden">
        <table class="w-full text-[13.5px]">
            <thead>
            <tr class="text-left text-[11px] uppercase tracking-wider text-[var(--ink-500)] border-b-2 border-[var(--line-strong)]">
                <th class="py-2 px-3">Title</th>
                <th class="py-2 px-3">Category</th>
                <th class="py-2 px-3">Owner</th>
                <th class="py-2 px-3">Version</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($documents as $document)
                <tr class="border-b border-[var(--line)]">
                    <td class="py-3 px-3">
                        <a href="{{ route('documents.show', $document) }}" class="font-medium hover:text-[var(--sig-600)]">{{ $document->title }}</a>
                        @if ($document->is_policy)
                            <span class="badge badge-info">Policy</span>
                        @endif
                    </td>
                    <td class="py-3 px-3">{{ $document->category?->name }}</td>
                    <td class="py-3 px-3">{{ $document->owner?->name }}</td>
                    <td class="py-3 px-3 font-mono text-xs">v{{ $document->currentVersion?->version_number ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-6 px-3 note">No documents match.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $documents->links() }}</div>
</div>
