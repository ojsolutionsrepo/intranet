<?php

namespace App\Modules\Dashboard\Livewire\Widgets;

use App\Models\User;
use Livewire\Component;
use Throwable;

class NewJoinersWidget extends Component
{
    public function render()
    {
        try {
            $joiners = User::query()
                ->with('profile')
                ->where('is_active', true)
                ->whereHas('profile', fn ($q) => $q->whereNotNull('start_date')->where('start_date', '>=', now()->subDays(90)))
                ->get()
                ->sortByDesc(fn (User $u) => $u->profile?->start_date?->timestamp ?? 0)
                ->take(5)
                ->values();

            return view('dashboard::widgets.new-joiners', [
                'joiners' => $joiners,
                'error' => null,
            ]);
        } catch (Throwable $e) {
            report($e);

            return view('dashboard::widgets.new-joiners', [
                'joiners' => collect(),
                'error' => 'New joiners temporarily unavailable.',
            ]);
        }
    }
}
