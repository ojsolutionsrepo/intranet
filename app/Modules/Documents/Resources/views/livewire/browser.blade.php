<div>
    <div class="card p-4 mb-5 grid gap-3 md:grid-cols-{{ $canManage ? '3' : '2' }}">
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
        @if ($canManage)
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm pb-2">
                    <input type="checkbox" wire:model.live="showTrashed">
                    Show trash
                </label>
            </div>
        @endif
    </div>

    @if ($showingTrashed)
        <p class="badge badge-warn mb-4">Showing trashed documents — restore within 30 days before permanent purge.</p>
    @endif

    <div class="card overflow-hidden">
        <table class="w-full text-[13.5px]">
            <thead>
            <tr class="text-left text-[11px] uppercase tracking-wider text-[var(--ink-500)] border-b-2 border-[var(--line-strong)]">
                <th class="py-2 px-3">Title</th>
                <th class="py-2 px-3">Category</th>
                <th class="py-2 px-3">Owner</th>
                <th class="py-2 px-3">Version</th>
                @if ($canManage)
                    <th class="py-2 px-3"></th>
                @endif
            </tr>
            </thead>
            <tbody>
            @forelse ($documents as $document)
                <tr class="border-b border-[var(--line)]">
                    <td class="py-3 px-3">
                        @if ($showingTrashed)
                            <span class="font-medium">{{ $document->title }}</span>
                            <span class="badge badge-warn">Trash</span>
                        @else
                            <a href="{{ route('documents.show', $document) }}" class="font-medium hover:text-[var(--sig-600)]">{{ $document->title }}</a>
                        @endif
                        @if ($document->is_policy)
                            <span class="badge badge-info">Policy</span>
                        @endif
                    </td>
                    <td class="py-3 px-3">{{ $document->category?->name }}</td>
                    <td class="py-3 px-3">{{ $document->owner?->name }}</td>
                    <td class="py-3 px-3 font-mono text-xs">v{{ $document->currentVersion?->version_number ?: '—' }}</td>
                    @if ($canManage)
                        <td class="py-3 px-3 text-right whitespace-nowrap">
                            @if ($showingTrashed)
                                <form method="POST" action="{{ route('documents.restore', $document->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm">Restore</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('documents.trash', $document) }}" class="inline"
                                      onsubmit="return confirm('Move this document to trash?');">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm text-[var(--err-600)]">Delete</button>
                                </form>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ $canManage ? 5 : 4 }}" class="py-6 px-3 note">{{ $showingTrashed ? 'Trash is empty.' : 'No documents match.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $documents->links() }}</div>
</div>
