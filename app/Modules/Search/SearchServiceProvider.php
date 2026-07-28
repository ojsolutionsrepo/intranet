<?php

namespace App\Modules\Search;

use App\Core\Modules\ModuleRegistry;
use App\Modules\Search\Http\Controllers\SearchController;
use App\Modules\Search\Livewire\Omnibox;
use App\Modules\Search\Livewire\SearchPage;
use App\Modules\Search\Services\SearchService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SearchService::class);
    }

    public function boot(ModuleRegistry $registry): void
    {
        $registry->register('search')
            ->permissions([
                'search.view' => 'Use global search',
            ])
            ->menu(fn () => [
                'label' => 'Search',
                'icon' => 'search',
                'route' => 'search.index',
                'permission' => 'search.view',
                'order' => 5,
            ])
            ->views(__DIR__.'/Resources/views', 'search');

        View::addNamespace('search', __DIR__.'/Resources/views');

        Livewire::component('search.page', SearchPage::class);
        Livewire::component('search.omnibox', Omnibox::class);

        Route::middleware(['web', 'auth', 'session.active'])
            ->prefix('search')
            ->group(function (): void {
                Route::get('/', [SearchController::class, 'index'])
                    ->name('search.index')
                    ->middleware(['can:search.view', 'throttle:search']);

                Route::get('/suggest', [SearchController::class, 'suggest'])
                    ->name('search.suggest')
                    ->middleware(['can:search.view', 'throttle:search']);
            });
    }
}
