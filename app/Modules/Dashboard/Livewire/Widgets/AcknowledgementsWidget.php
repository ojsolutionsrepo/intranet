<?php

namespace App\Modules\Dashboard\Livewire\Widgets;

use App\Modules\Documents\Services\PolicyService;
use Livewire\Component;
use Throwable;

class AcknowledgementsWidget extends Component
{
    public function render(PolicyService $policies)
    {
        try {
            $user = auth()->user();
            $outstanding = $policies->hubFor($user)
                ->filter(fn ($doc) => $doc->mandatory_ack && ! $policies->hasAcknowledgedCurrent($doc, $user))
                ->take(5)
                ->values();

            return view('dashboard::widgets.acknowledgements', [
                'items' => $outstanding,
                'error' => null,
            ]);
        } catch (Throwable $e) {
            report($e);

            return view('dashboard::widgets.acknowledgements', [
                'items' => collect(),
                'error' => 'Acknowledgements temporarily unavailable.',
            ]);
        }
    }
}
