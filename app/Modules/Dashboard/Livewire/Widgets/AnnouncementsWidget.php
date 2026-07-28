<?php

namespace App\Modules\Dashboard\Livewire\Widgets;

use App\Models\User;
use App\Modules\News\Services\NewsService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Throwable;

class AnnouncementsWidget extends Component
{
    public function render(NewsService $news)
    {
        try {
            $user = Auth::user();
            abort_unless($user instanceof User, 403);

            $posts = $news->feedFor($user, 5)->items();

            return view('dashboard::widgets.announcements', [
                'posts' => $posts,
                'error' => null,
            ]);
        } catch (Throwable $e) {
            report($e);

            return view('dashboard::widgets.announcements', [
                'posts' => [],
                'error' => 'Announcements temporarily unavailable.',
            ]);
        }
    }
}
