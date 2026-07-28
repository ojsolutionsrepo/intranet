<div
    x-data="{ open: @entangle('open') }"
    @keydown.window.prevent.cmd.k="$wire.openBox()"
    @keydown.window.prevent.ctrl.k="$wire.openBox()"
    @keydown.escape.window="$wire.closeBox()"
>
    @if ($open)
        <div class="fixed inset-0 z-50 bg-black/40 flex items-start justify-center pt-[12vh] px-4" wire:click="closeBox">
            <div class="card w-full max-w-xl p-0 overflow-hidden" @click.stop>
                <div class="p-3 border-b border-[var(--line)]">
                    <input
                        type="search"
                        wire:model.live.debounce.200ms="q"
                        class="input border-0 shadow-none focus:ring-0"
                        placeholder="Search the intranet…"
                        autofocus
                    >
                </div>
                <ul class="max-h-[50vh] overflow-y-auto">
                    @forelse ($hits as $hit)
                        <li>
                            <a href="{{ $hit['url'] }}" class="block px-4 py-3 hover:bg-[var(--paper-2)] border-b border-[var(--line)]">
                                <span class="badge badge-info">{{ $hit['type'] }}</span>
                                <span class="font-medium ml-2">{{ $hit['title'] }}</span>
                                @if (! empty($hit['subtitle']))
                                    <div class="note mt-0.5">{{ $hit['subtitle'] }}</div>
                                @endif
                            </a>
                        </li>
                    @empty
                        <li class="px-4 py-6 note">{{ strlen($q) < 2 ? 'Type at least 2 characters…' : 'No matches.' }}</li>
                    @endforelse
                </ul>
                <div class="px-4 py-2 note border-t border-[var(--line)] flex justify-between">
                    <span>Esc to close</span>
                    <a href="{{ route('search.index', ['q' => $q]) }}" class="text-[var(--sig-600)]">Open full search</a>
                </div>
            </div>
        </div>
    @endif
</div>
