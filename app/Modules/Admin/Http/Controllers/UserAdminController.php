<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class UserAdminController extends Controller
{
    public function index(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('admin'), 404);

        return view('admin-module::users.index');
    }

    public function create(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('admin'), 404);

        return view('admin-module::users.form', ['userId' => null]);
    }

    public function edit(ModuleRegistry $registry, User $user): View
    {
        abort_unless($registry->isEnabled('admin'), 404);

        return view('admin-module::users.form', ['userId' => $user->id]);
    }
}
