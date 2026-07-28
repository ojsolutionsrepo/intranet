<div>
    <form wire:submit="save" class="space-y-4 max-w-lg">
        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5">Site name</label>
            <input type="text" class="input" wire:model="site_name">
            @error('site_name') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
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
        <button type="submit" class="btn btn-primary">Save settings</button>
    </form>
</div>
