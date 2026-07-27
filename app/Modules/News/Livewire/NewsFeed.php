<?php

namespace App\Modules\News\Livewire;

use App\Modules\News\Services\NewsService;
use Livewire\Component;
use Livewire\WithPagination;

class NewsFeed extends Component
{
    use WithPagination;

    public function render(NewsService $news)
    {
        $user = auth()->user();

        return view('news::livewire.feed', [
            'posts' => $news->feedFor($user),
            'alerts' => $news->alertBannerFor($user),
        ]);
    }
}
