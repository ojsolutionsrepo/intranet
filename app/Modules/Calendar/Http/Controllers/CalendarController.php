<?php

namespace App\Modules\Calendar\Http\Controllers;

use App\Core\Modules\ModuleRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class CalendarController extends Controller
{
    public function index(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('calendar'), 404);

        return view('calendar::index');
    }

    public function create(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('calendar'), 404);

        return view('calendar::create');
    }
}
