<?php

namespace App\Modules\Directory\Http\Controllers;

use App\Core\Modules\ModuleRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class ProfileController extends Controller
{
    public function edit(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('directory'), 404);

        return view('directory::profile-edit');
    }
}
