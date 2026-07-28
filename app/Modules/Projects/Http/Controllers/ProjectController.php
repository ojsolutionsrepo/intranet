<?php

namespace App\Modules\Projects\Http\Controllers;

use App\Core\Modules\ModuleRegistry;
use App\Modules\Projects\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('projects'), 404);

        return view('projects::index');
    }

    public function create(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('projects'), 404);

        return view('projects::create');
    }

    public function show(ModuleRegistry $registry, Project $project): View
    {
        abort_unless($registry->isEnabled('projects'), 404);
        Gate::authorize('view', $project);

        $project->load('milestones');

        return view('projects::show', compact('project'));
    }
}
