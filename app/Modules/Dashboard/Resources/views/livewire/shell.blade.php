<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @foreach ($widgets as $widget)
        <div class="min-h-[140px]" wire:key="widget-{{ $widget['key'] }}">
            @livewire($widget['component'], key($widget['key'].'-'.auth()->id()))
        </div>
    @endforeach
</div>
