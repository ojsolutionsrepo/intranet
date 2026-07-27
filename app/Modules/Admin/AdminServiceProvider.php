<?php

namespace App\Modules\Admin;

use App\Core\Modules\ModuleRegistry;
use App\Modules\Admin\Http\Controllers\PermissionMatrixController;
use App\Modules\Admin\Http\Controllers\UserAdminController;
use App\Modules\Admin\Livewire\PermissionMatrix;
use App\Modules\Admin\Livewire\UserForm;
use App\Modules\Admin\Livewire\UserIndex;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AdminServiceProvider extends ServiceProvider
{
    public function boot(ModuleRegistry $registry): void
    {
        $registry->register('admin')
            ->permissions([
                'admin.users.manage' => 'Create, update, and deactivate users',
                'admin.permissions.manage' => 'Edit the role permission matrix',
                'admin.settings.manage' => 'Change site settings',
            ])
            ->menu(fn () => null)
            ->views(__DIR__.'/Resources/views', 'admin-module');

        View::addNamespace('admin-module', __DIR__.'/Resources/views');

        Livewire::component('admin.user-index', UserIndex::class);
        Livewire::component('admin.user-form', UserForm::class);
        Livewire::component('admin.permission-matrix', PermissionMatrix::class);

        Route::middleware(['web', 'auth', 'session.active', 'admin'])
            ->prefix('admin')
            ->group(function () use ($registry): void {
                Route::get('/users', [UserAdminController::class, 'index'])
                    ->name('admin.users.index')
                    ->middleware('can:admin.users.manage');

                Route::get('/users/create', [UserAdminController::class, 'create'])
                    ->name('admin.users.create')
                    ->middleware('can:admin.users.manage');

                Route::get('/users/{user}/edit', [UserAdminController::class, 'edit'])
                    ->name('admin.users.edit')
                    ->middleware('can:admin.users.manage');

                Route::get('/permissions', [PermissionMatrixController::class, 'index'])
                    ->name('admin.permissions')
                    ->middleware('can:admin.permissions.manage');
            });
    }
}
