<?php

namespace App\Modules\News\Http\Controllers;

use App\Core\Modules\ModuleRegistry;
use App\Modules\News\Models\Post;
use App\Modules\News\Services\NewsService;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class NewsController extends Controller
{
    public function index(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('news'), 404);

        return view('news::index');
    }

    public function create(ModuleRegistry $registry): View
    {
        abort_unless($registry->isEnabled('news'), 404);
        Gate::authorize('create', Post::class);

        return view('news::create');
    }

    public function show(ModuleRegistry $registry, Post $post, NewsService $news): View
    {
        abort_unless($registry->isEnabled('news'), 404);
        Gate::authorize('view', $post);

        $news->markRead($post, request()->user());

        return view('news::show', compact('post'));
    }
}
