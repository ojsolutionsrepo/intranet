<?php

namespace App\Modules\Dashboard\Livewire;

use App\Modules\Dashboard\Services\WidgetRegistry;
use App\Shared\Services\Analytics;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardShell extends Component
{
    public function mount(Analytics $analytics): void
    {
        $analytics->track('dashboard.viewed');
    }

    public function render(WidgetRegistry $registry)
    {
        return view('dashboard::livewire.shell', [
            'widgets' => $registry->forUser(Auth::user()),
        ]);
    }
}
