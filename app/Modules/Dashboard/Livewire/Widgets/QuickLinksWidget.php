<?php

namespace App\Modules\Dashboard\Livewire\Widgets;

use App\Modules\Dashboard\Models\QuickLink;
use Livewire\Component;
use Throwable;

class QuickLinksWidget extends Component
{
    public function render()
    {
        try {
            $links = QuickLink::query()->forStaff()->get()->map(fn (QuickLink $link) => [
                'label' => $link->label,
                'href' => $link->href(),
                'category' => $link->category,
                'opens_external' => $link->opens_external,
                'description' => $link->description,
            ]);

            if ($links->isEmpty()) {
                $links = collect([
                    ['label' => 'Projects', 'href' => route('projects.index'), 'category' => 'internal', 'opens_external' => false, 'description' => null],
                    ['label' => 'Directory', 'href' => route('directory.index'), 'category' => 'internal', 'opens_external' => false, 'description' => null],
                    ['label' => 'Documents', 'href' => route('documents.index'), 'category' => 'internal', 'opens_external' => false, 'description' => null],
                ]);
            }

            return view('dashboard::widgets.quick-links', [
                'grouped' => $links->groupBy('category'),
                'error' => null,
            ]);
        } catch (Throwable $e) {
            report($e);

            return view('dashboard::widgets.quick-links', [
                'grouped' => collect(),
                'error' => 'Quick links temporarily unavailable.',
            ]);
        }
    }
}
