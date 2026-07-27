<?php

namespace App\Modules\Directory;

use App\Core\Modules\ModuleRegistry;
use App\Modules\Directory\Http\Controllers\DirectoryController;
use App\Modules\Directory\Http\Controllers\ImportController;
use App\Modules\Directory\Http\Controllers\ProfileController;
use App\Modules\Directory\Livewire\DirectoryIndex;
use App\Modules\Directory\Livewire\OrgChart;
use App\Modules\Directory\Livewire\ProfileEdit;
use App\Modules\Directory\Livewire\StaffImport;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class DirectoryServiceProvider extends ServiceProvider
{
    public function boot(ModuleRegistry $registry): void
    {
        $registry->register('directory')
            ->permissions([
                'directory.view' => 'Browse the staff directory',
                'directory.import' => 'Import staff from CSV/XLSX',
                'directory.manage' => 'Manage departments and profiles as admin',
            ])
            ->menu(fn () => [
                'label' => 'Directory',
                'icon' => 'users',
                'route' => 'directory.index',
                'permission' => 'directory.view',
                'order' => 10,
            ])
            ->views(__DIR__.'/Resources/views', 'directory');

        View::addNamespace('directory', __DIR__.'/Resources/views');

        Livewire::component('directory.index', DirectoryIndex::class);
        Livewire::component('directory.org-chart', OrgChart::class);
        Livewire::component('directory.profile-edit', ProfileEdit::class);
        Livewire::component('directory.staff-import', StaffImport::class);

        Route::middleware(['web', 'auth', 'session.active'])
            ->prefix('directory')
            ->group(function () use ($registry): void {
                Route::get('/', [DirectoryController::class, 'index'])
                    ->name('directory.index')
                    ->middleware('can:directory.view');

                Route::get('/org-chart', [DirectoryController::class, 'orgChart'])
                    ->name('directory.org-chart')
                    ->middleware('can:directory.view');

                Route::get('/departments/{department:slug}', [DirectoryController::class, 'department'])
                    ->name('directory.department')
                    ->middleware('can:directory.view');

                Route::get('/people/{user}', [DirectoryController::class, 'show'])
                    ->name('directory.show')
                    ->middleware('can:directory.view');

                Route::get('/profile/edit', [ProfileController::class, 'edit'])
                    ->name('directory.profile.edit')
                    ->middleware('can:directory.view');

                Route::middleware('can:directory.import')->group(function () {
                    Route::get('/import', [ImportController::class, 'index'])->name('directory.import');
                });
            });
    }
}
