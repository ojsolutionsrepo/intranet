<?php

namespace App\Modules\Documents\Livewire;

use App\Modules\Documents\Services\PolicyService;
use Livewire\Component;

class PolicyHub extends Component
{
    public function render(PolicyService $policies)
    {
        $user = auth()->user();
        $docs = $policies->hubFor($user)->map(function ($doc) use ($policies, $user) {
            $doc->ack_status = $policies->hasAcknowledgedCurrent($doc, $user) ? 'acknowledged' : 'outstanding';
            $doc->review_chip = $doc->reviewStatus();

            return $doc;
        });

        return view('documents::livewire.policy-hub', [
            'policies' => $docs,
        ]);
    }
}
