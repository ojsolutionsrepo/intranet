<?php

namespace App\Modules\Directory\Http\Controllers;

use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use App\Shared\Models\Department;
use App\Shared\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class DirectoryController extends Controller
{
    public function index(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('directory'), 404);

        return view('directory::index');
    }

    public function show(ModuleRegistry $registry, User $user, AuditLogger $audit): View
    {
        abort_unless($registry->isEnabled('directory'), 404);
        abort_unless($user->is_active, 404);

        $user->load(['profile', 'departments', 'teams', 'roles', 'manager.profile', 'directReports.profile']);

        $audit->log('directory.profile_viewed', $user);

        return view('directory::show', compact('user'));
    }

    public function department(ModuleRegistry $registry, Department $department): View
    {
        abort_unless($registry->isEnabled('directory'), 404);

        $department->load([
            'lead.profile',
            'teams.users.profile',
            'users.profile',
            'users.roles',
            'children',
            'parent',
        ]);

        return view('directory::department', compact('department'));
    }

    public function orgChart(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('directory'), 404);

        return view('directory::org-chart');
    }
}
