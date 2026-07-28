<?php

namespace App\Modules\Projects\Policies;

use App\Models\User;
use App\Modules\Projects\Models\Project;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $user->can('projects.view') && $project->isVisibleTo($user);
    }

    public function manage(User $user): bool
    {
        return $user->can('projects.manage');
    }
}
