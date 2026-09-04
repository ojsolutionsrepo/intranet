<div>
    @if (session('status'))
        <p class="badge badge-ok mb-4">{{ session('status') }}</p>
    @endif

    <form wire:submit="save" class="space-y-5">
        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5">Site name</label>
            <input type="text" class="input" wire:model="site_name">
            @error('site_name') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <fieldset class="space-y-4 pt-2 border-t border-[var(--line)]">
            <legend class="font-display font-semibold text-sm text-[var(--ink-900)] mb-1">Branding</legend>
            <p class="note text-xs">Accent colour, logo, and favicon appear across the staff portal (UR-ADM-05). Very dark accents are lifted automatically in dark mode for contrast.</p>

            <div>
                <label class="block text-[12.5px] font-semibold mb-1.5">Accent colour</label>
                <div class="flex flex-wrap items-center gap-3">
                    <input type="color" class="h-10 w-14 rounded-md border border-[var(--line-strong)] bg-transparent cursor-pointer p-1"
                           wire:model.blur="brand_color" aria-label="Accent colour picker">
                    <input type="text" class="input max-w-[9rem] font-mono text-sm" wire:model.blur="brand_color" placeholder="#d97b22" maxlength="7">
                </div>
                @error('brand_color') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </fieldset>

        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5">Session idle timeout (minutes)</label>
            <input type="number" class="input" wire:model="session_idle_timeout" min="5" max="1440">
            @error('session_idle_timeout') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5">Privacy contact email</label>
            <input type="email" class="input" wire:model="privacy_contact">
            @error('privacy_contact') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Save settings</span>
            <span wire:loading wire:target="save">Saving…</span>
        </button>
    </form>

    {{-- Classic multipart uploads: Livewire file uploads need PHP tmpfile()/fileinfo, often disabled on shared hosts. --}}
    <div class="space-y-5 mt-6 pt-5 border-t border-[var(--line)]">
        <h2 class="font-display font-semibold text-sm text-[var(--ink-900)]">Logo &amp; favicon</h2>
        <p class="note text-xs">Upload each file with its own Save button. This path works on hosts that block Livewire temporary uploads.</p>

        <form method="POST" action="{{ route('admin.settings.logo') }}" enctype="multipart/form-data" class="space-y-2">
            @csrf
            <label class="block text-[12.5px] font-semibold mb-1.5">Site logo</label>
            @if ($logoUrl)
                <div class="flex items-center gap-3 mb-2">
                    <img src="{{ $logoUrl }}" alt="Current logo" class="h-10 max-w-[160px] object-contain rounded-md border border-[var(--line)] bg-[var(--paper-2)] p-1">
                    <button type="submit" form="remove-logo-form" class="btn btn-ghost btn-sm" onclick="return confirm('Remove the site logo?')">Remove</button>
                </div>
            @endif
            <div class="flex flex-wrap items-end gap-3">
                <input type="file" name="logo" class="input flex-1 min-w-[12rem]" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml,.svg" required>
                <button type="submit" class="btn btn-secondary btn-sm">Upload logo</button>
            </div>
            <p class="note text-xs">PNG, SVG, JPG, or WebP · max 2&nbsp;MB. Replaces the OJ mark in the sidebar.</p>
            @error('logo') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </form>

        <form method="POST" action="{{ route('admin.settings.favicon') }}" enctype="multipart/form-data" class="space-y-2">
            @csrf
            <label class="block text-[12.5px] font-semibold mb-1.5">Favicon</label>
            @if ($faviconUrl)
                <div class="flex items-center gap-3 mb-2">
                    <img src="{{ $faviconUrl }}" alt="Current favicon" class="h-8 w-8 object-contain rounded border border-[var(--line)] bg-[var(--paper-2)] p-0.5">
                    <button type="submit" form="remove-favicon-form" class="btn btn-ghost btn-sm" onclick="return confirm('Remove the favicon?')">Remove</button>
                </div>
            @endif
            <div class="flex flex-wrap items-end gap-3">
                <input type="file" name="favicon" class="input flex-1 min-w-[12rem]" accept=".ico,image/png,image/jpeg,image/gif,image/svg+xml,image/webp" required>
                <button type="submit" class="btn btn-secondary btn-sm">Upload favicon</button>
            </div>
            <p class="note text-xs">ICO, PNG, SVG, or WebP · max 512&nbsp;KB. Browser tab icon.</p>
            @error('favicon') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </form>
    </div>

    <form id="remove-logo-form" method="POST" action="{{ route('admin.settings.logo.remove') }}" class="hidden">@csrf @method('DELETE')</form>
    <form id="remove-favicon-form" method="POST" action="{{ route('admin.settings.favicon.remove') }}" class="hidden">@csrf @method('DELETE')</form>
</div>
