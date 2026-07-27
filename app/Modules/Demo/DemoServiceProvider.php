<?php

namespace App\Modules\Demo;

use App\Core\Modules\ModuleRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class DemoServiceProvider extends ServiceProvider
{
    public function boot(ModuleRegistry $registry): void
    {
        $registry->register('demo')
            ->permissions([
                'demo.view' => 'View the demo module page',
            ])
            ->menu(fn () => [
                'label' => 'Demo',
                'icon' => 'beaker',
                'route' => 'demo.index',
                'permission' => 'demo.view',
                'order' => 90,
            ])
            ->views(__DIR__.'/Resources/views', 'demo');

        View::addNamespace('demo', __DIR__.'/Resources/views');

        Route::middleware(['web', 'auth'])
            ->prefix('demo')
            ->group(function () use ($registry): void {
                Route::get('/', function () use ($registry) {
                    abort_unless($registry->isEnabled('demo'), 404);

                    return View::make('demo::page');
                })->name('demo.index');
            });
    }
}
