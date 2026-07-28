<?php

namespace App\Modules\Admin\Livewire;

use App\Modules\Dashboard\Models\QuickLink;
use Livewire\Component;

class QuickLinkManager extends Component
{
    public string $label = '';

    public string $url = '';

    public string $category = 'platform';

    public string $description = '';

    public bool $opens_external = true;

    public function save(): void
    {
        $this->validate([
            'label' => 'required|string|max:120',
            'url' => 'required|string|max:500',
            'category' => 'required|in:internal,platform,comms',
            'description' => 'nullable|string|max:255',
        ]);

        $max = (int) QuickLink::query()->max('sort_order');

        QuickLink::query()->create([
            'label' => $this->label,
            'url' => $this->url,
            'category' => $this->category,
            'description' => $this->description ?: null,
            'opens_external' => $this->opens_external,
            'staff_visible' => true,
            'is_active' => true,
            'sort_order' => $max + 10,
        ]);

        $this->reset(['label', 'url', 'description']);
        $this->category = 'platform';
        $this->opens_external = true;
        session()->flash('status', 'Quick link added — visible on staff dashboards.');
    }

    public function toggle(int $id): void
    {
        $link = QuickLink::query()->findOrFail($id);
        $link->update(['is_active' => ! $link->is_active]);
    }

    public function delete(int $id): void
    {
        QuickLink::query()->whereKey($id)->delete();
    }

    public function render()
    {
        return view('admin-module::livewire.quick-links', [
            'links' => QuickLink::query()->orderBy('sort_order')->orderBy('label')->get(),
        ]);
    }
}
