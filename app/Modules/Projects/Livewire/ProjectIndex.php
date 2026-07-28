<?php

namespace App\Modules\Projects\Livewire;

use App\Models\User;
use App\Modules\Projects\Services\ProjectSyncService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ProjectIndex extends Component
{
    use WithPagination;

    public function render(ProjectSyncService $projects)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 403);

        return view('projects::livewire.index', [
            'projects' => $projects->visibleFor($user),
        ]);
    }
}
