<div>
    <form wire:submit="save" class="space-y-4">
        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5">Project name</label>
            <input type="text" class="input" wire:model="name" required>
            @error('name') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5">Summary</label>
            <textarea class="input" rows="3" wire:model="summary"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-[12.5px] font-semibold mb-1.5">RAG</label>
                <select class="input" wire:model="rag">
                    <option value="green">Green</option>
                    <option value="amber">Amber</option>
                    <option value="red">Red</option>
                </select>
            </div>
            <div>
                <label class="block text-[12.5px] font-semibold mb-1.5">Status</label>
                <select class="input" wire:model="status">
                    <option value="active">Active</option>
                    <option value="on_hold">On hold</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5">Deep link (Plane / Governex / Drive folder)</label>
            <input type="url" class="input" wire:model="deep_link" placeholder="https://…">
            @error('deep_link') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-[12.5px] font-semibold mb-1.5">First milestone (optional)</label>
            <input type="text" class="input" wire:model="milestone_title">
        </div>
        <button type="submit" class="btn btn-primary">Publish to staff dashboards</button>
    </form>
</div>
