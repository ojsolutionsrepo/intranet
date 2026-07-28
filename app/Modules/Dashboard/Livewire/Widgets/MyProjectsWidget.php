<?php

namespace App\Modules\Dashboard\Livewire\Widgets;

use App\Models\User;
use App\Modules\Projects\Services\ProjectSyncService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Throwable;

class MyProjectsWidget extends Component
{
    public function render(ProjectSyncService $projects)
    {
        try {
            $user = Auth::user();
            abort_unless($user instanceof User, 403);

            $items = $projects->forWidget($user, 5);

            return view('dashboard::widgets.my-projects', [
                'projects' => $items,
                'error' => null,
            ]);
        } catch (Throwable $e) {
            report($e);

            return view('dashboard::widgets.my-projects', [
                'projects' => collect(),
                'error' => 'Projects temporarily unavailable.',
            ]);
        }
    }
}
