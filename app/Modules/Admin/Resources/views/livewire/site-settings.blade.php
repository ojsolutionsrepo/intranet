<div>
    @if (session('status'))
        <p class="badge badge-ok mb-4">{{ session('status') }}</p>
    @endif

    {{-- Live accent preview while editing (overrides shell tokens until Save + redirect). --}}
    @if (preg_match('/^#[0-9A-Fa-f]{6}$/', $brand_color))
        <style id="oj-brand-accent-preview">
            :root,
            html[data-theme="dark"],
            html[data-theme="light"],
            html[data-theme="system"],
            html[data-theme="system"][data-theme-resolved="dark"],
            html[data-theme="system"][data-theme-resolved="light"] {
                {!! app(\App\Shared\Services\Branding::class)->accentCssFor($brand_color) !!}
            }
        </style>
    @endif

    <form wire:submit="save" class="space-y-5">
        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5">Site name</label>
            <input type="text" class="input" wire:model="site_name">
            @error('site_name') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <fieldset class="space-y-4 pt-2 border-t border-[var(--line)]">
            <legend class="font-display font-semibold text-sm text-[var(--ink-900)] mb-1">Branding</legend>
            <p class="note text-xs">Accent colour, logo, and favicon appear across the staff portal (UR-ADM-05).</p>

            <div>
                <label class="block text-[12.5px] font-semibold mb-1.5">Accent colour</label>
                <div class="flex flex-wrap items-center gap-3">
                    <input type="color" class="h-10 w-14 rounded-md border border-[var(--line-strong)] bg-transparent cursor-pointer p-1"
                           wire:model.live="brand_color" aria-label="Accent colour picker">
                    <input type="text" class="input max-w-[9rem] font-mono text-sm" wire:model.live="brand_color" placeholder="#d97b22" maxlength="7">
                    <span class="inline-flex items-center gap-2 text-xs note">
                        <span
                            class="w-3 h-3 rounded-full inline-block"
                            style="background: {{ $brand_color }}; box-shadow: 0 0 10px {{ $brand_color }};"
                        ></span>
                        Preview
                    </span>
                    <span class="btn btn-primary btn-sm pointer-events-none">Sample button</span>
                </div>
                @error('brand_color') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[12.5px] font-semibold mb-1.5">Site logo</label>
                @if ($logoUrl)
                    <div class="flex items-center gap-3 mb-2">
                        <img src="{{ $logoUrl }}" alt="Current logo" class="h-10 max-w-[160px] object-contain rounded-md border border-[var(--line)] bg-[var(--paper-2)] p-1">
                        <button type="button" class="btn btn-ghost btn-sm" wire:click="removeLogo" wire:confirm="Remove the site logo?">Remove</button>
                    </div>
                @endif
                <input type="file" class="input" wire:model="logo" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml,.svg">
                <p class="note text-xs mt-1">PNG, SVG, JPG, or WebP · max 2&nbsp;MB. Replaces the OJ mark in the sidebar.</p>
                <div wire:loading wire:target="logo" class="note text-xs mt-1">Uploading…</div>
                @error('logo') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-[12.5px] font-semibold mb-1.5">Favicon</label>
                @if ($faviconUrl)
                    <div class="flex items-center gap-3 mb-2">
                        <img src="{{ $faviconUrl }}" alt="Current favicon" class="h-8 w-8 object-contain rounded border border-[var(--line)] bg-[var(--paper-2)] p-0.5">
                        <button type="button" class="btn btn-ghost btn-sm" wire:click="removeFavicon" wire:confirm="Remove the favicon?">Remove</button>
                    </div>
                @endif
                <input type="file" class="input" wire:model="favicon" accept=".ico,image/png,image/jpeg,image/gif,image/svg+xml,image/webp">
                <p class="note text-xs mt-1">ICO, PNG, SVG, or WebP · max 512&nbsp;KB. Browser tab icon.</p>
                <div wire:loading wire:target="favicon" class="note text-xs mt-1">Uploading…</div>
                @error('favicon') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
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
</div>
