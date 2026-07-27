<?php

namespace App\Modules\News;

use App\Core\Modules\ModuleRegistry;
use App\Modules\News\Http\Controllers\NewsController;
use App\Modules\News\Livewire\NewsComposer;
use App\Modules\News\Livewire\NewsFeed;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class NewsServiceProvider extends ServiceProvider
{
    public function boot(ModuleRegistry $registry): void
    {
        $registry->register('news')
            ->permissions([
                'news.view' => 'View the news feed',
                'news.publish' => 'Create and publish news posts',
                'news.manage' => 'Moderate all news posts',
            ])
            ->menu(fn () => [
                'label' => 'News',
                'icon' => 'megaphone',
                'route' => 'news.index',
                'permission' => 'news.view',
                'order' => 20,
            ])
            ->views(__DIR__.'/Resources/views', 'news');

        View::addNamespace('news', __DIR__.'/Resources/views');

        Livewire::component('news.feed', NewsFeed::class);
        Livewire::component('news.composer', NewsComposer::class);

        Route::middleware(['web', 'auth', 'session.active'])
            ->prefix('news')
            ->group(function () use ($registry): void {
                Route::get('/', [NewsController::class, 'index'])
                    ->name('news.index')
                    ->middleware('can:news.view');

                Route::get('/create', [NewsController::class, 'create'])
                    ->name('news.create')
                    ->middleware('can:news.publish');

                Route::get('/{post:slug}', [NewsController::class, 'show'])
                    ->name('news.show')
                    ->middleware('can:news.view');
            });
    }
}
