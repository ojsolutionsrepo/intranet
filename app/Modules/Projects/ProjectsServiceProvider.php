<?php

namespace App\Modules\Projects;

use App\Core\Modules\ModuleRegistry;
use App\Modules\Projects\Http\Controllers\ProjectController;
use App\Modules\Projects\Livewire\ProjectForm;
use App\Modules\Projects\Livewire\ProjectIndex;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Policies\ProjectPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class ProjectsServiceProvider extends ServiceProvider
{
    public function boot(ModuleRegistry $registry): void
    {
        $registry->register('projects')
            ->permissions([
                'projects.view' => 'View projects list and detail',
                'projects.manage' => 'Manage manual projects and sync',
            ])
            ->menu(fn () => [
                'label' => 'Projects',
                'icon' => 'folder',
                'route' => 'projects.index',
                'permission' => 'projects.view',
                'order' => 35,
            ])
            ->views(__DIR__.'/Resources/views', 'projects');

        View::addNamespace('projects', __DIR__.'/Resources/views');
        Gate::policy(Project::class, ProjectPolicy::class);

        Livewire::component('projects.index', ProjectIndex::class);
        Livewire::component('projects.form', ProjectForm::class);

        Route::middleware(['web', 'auth', 'session.active'])
            ->prefix('projects')
            ->group(function (): void {
                Route::get('/', [ProjectController::class, 'index'])
                    ->name('projects.index')
                    ->middleware('can:projects.view');

                Route::get('/create', [ProjectController::class, 'create'])
                    ->name('projects.create')
                    ->middleware('can:projects.manage');

                Route::get('/{project:slug}', [ProjectController::class, 'show'])
                    ->name('projects.show')
                    ->middleware('can:projects.view');
            });
    }
}
