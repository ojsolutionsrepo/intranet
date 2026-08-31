<div>
    <form wire:submit="save" class="space-y-4">
        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5" for="drive-client-id">Client ID</label>
            <input id="drive-client-id" type="text" class="input font-mono text-sm" wire:model="client_id"
                   placeholder="xxxxx.apps.googleusercontent.com" autocomplete="off">
            @error('client_id') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5" for="drive-client-secret">Client secret</label>
            <input id="drive-client-secret" type="password" class="input font-mono text-sm" wire:model="client_secret"
                   placeholder="GOCSPX-…" autocomplete="off">
            @error('client_secret') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5" for="drive-folder-id">
                Folder ID <span class="font-normal note">(optional)</span>
            </label>
            <input id="drive-folder-id" type="text" class="input font-mono text-sm" wire:model="folder_id"
                   placeholder="Shared Drive / folder id" autocomplete="off">
            @error('folder_id') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-start gap-2.5 cursor-pointer">
            <input type="checkbox" class="mt-0.5" wire:model.live="enabled">
            <span>
                <span class="block text-[12.5px] font-semibold">Enable Drive broker</span>
                <span class="note text-xs">Writes <span class="font-mono">DRIVE_BROKER_ENABLED=true</span> to <span class="font-mono">.env</span></span>
            </span>
        </label>
        @error('enabled') <p class="text-[var(--err-600)] text-xs">{{ $message }}</p> @enderror

        <div class="rounded-md border border-[var(--line)] bg-[var(--surface-2)] p-3">
            <p class="note text-xs mb-1">Authorized redirect URI (paste into Google Cloud Console):</p>
            <p class="font-mono text-[12px] break-all">{{ $callbackUrl }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">Save to .env</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
            <p class="note text-xs">Values are written to <span class="font-mono">.env</span> and applied immediately.</p>
        </div>
    </form>
</div>
