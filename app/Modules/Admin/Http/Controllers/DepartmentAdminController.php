<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Core\Modules\ModuleRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class DepartmentAdminController extends Controller
{
    public function index(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('admin'), 404);

        return view('admin-module::departments');
    }
}
