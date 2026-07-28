<?php

namespace App\Modules\Admin\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class QuickLinkAdminController extends Controller
{
    public function index(): View
    {
        return view('admin-module::quick-links');
    }
}
