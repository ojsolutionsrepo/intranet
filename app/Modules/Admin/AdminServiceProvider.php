<?php

namespace App\Modules\Admin;

use App\Core\Modules\ModuleRegistry;
use App\Modules\Admin\Http\Controllers\BrandingUploadController;
use App\Modules\Admin\Http\Controllers\ComplianceController;
use App\Modules\Admin\Http\Controllers\DepartmentAdminController;
use App\Modules\Admin\Http\Controllers\IntegrationHealthController;
use App\Modules\Admin\Http\Controllers\PermissionMatrixController;
use App\Modules\Admin\Http\Controllers\QuickLinkAdminController;
use App\Modules\Admin\Http\Controllers\UserAdminController;
use App\Modules\Admin\Livewire\DepartmentManager;
use App\Modules\Admin\Livewire\DriveCredentialsForm;
use App\Modules\Admin\Livewire\PermissionMatrix;
use App\Modules\Admin\Livewire\QuickLinkManager;
use App\Modules\Admin\Livewire\SiteSettingsForm;
use App\Modules\Admin\Livewire\UserForm;
use App\Modules\Admin\Livewire\UserIndex;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminServiceProvider extends ServiceProvider
{
    public function boot(ModuleRegistry $registry): void
    {
        $registry->register('admin')
            ->permissions([
                'admin.users.manage' => 'Create, update, and deactivate users',
                'admin.departments.manage' => 'Create, update, and remove departments',
                'admin.permissions.manage' => 'Edit the role permission matrix',
                'admin.settings.manage' => 'Change site settings',
                'admin.integrations.view' => 'View integration health and trigger sync',
                'admin.quick_links.manage' => 'Manage dashboard quick links and platform SSO launches',
                'admin.compliance.export' => 'Export subject-access packs and audit logs',
            ])
            ->menu(fn () => null)
            ->views(__DIR__.'/Resources/views', 'admin-module');

        View::addNamespace('admin-module', __DIR__.'/Resources/views');

        Livewire::component('admin.user-index', UserIndex::class);
        Livewire::component('admin.user-form', UserForm::class);
        Livewire::component('admin.department-manager', DepartmentManager::class);
        Livewire::component('admin.permission-matrix', PermissionMatrix::class);
        Livewire::component('admin.quick-link-manager', QuickLinkManager::class);
        Livewire::component('admin.site-settings', SiteSettingsForm::class);
        Livewire::component('admin.drive-credentials', DriveCredentialsForm::class);

        // Existing installs: ensure Admin role receives newly registered permissions.
        try {
            Permission::findOrCreate('admin.departments.manage');
            Role::findByName('Admin')?->givePermissionTo('admin.departments.manage');
        } catch (\Throwable) {
            // Roles/permissions tables may not exist yet during first install.
        }

        Route::middleware(['web', 'auth', 'session.active'])
            ->get('/privacy', [ComplianceController::class, 'privacy'])
            ->name('privacy.notice');

        Route::middleware(['web', 'auth', 'session.active', 'admin'])
            ->prefix('admin')
            ->group(function (): void {
                Route::get('/users', [UserAdminController::class, 'index'])
                    ->name('admin.users.index')
                    ->middleware('can:admin.users.manage');

                Route::get('/users/create', [UserAdminController::class, 'create'])
                    ->name('admin.users.create')
                    ->middleware('can:admin.users.manage');

                Route::get('/users/{user}/edit', [UserAdminController::class, 'edit'])
                    ->name('admin.users.edit')
                    ->middleware('can:admin.users.manage');

                Route::get('/departments', [DepartmentAdminController::class, 'index'])
                    ->name('admin.departments')
                    ->middleware('can:admin.departments.manage');

                Route::get('/permissions', [PermissionMatrixController::class, 'index'])
                    ->name('admin.permissions')
                    ->middleware('can:admin.permissions.manage');

                Route::get('/integrations', [IntegrationHealthController::class, 'index'])
                    ->name('admin.integrations')
                    ->middleware('can:admin.integrations.view');

                Route::post('/integrations/sync', [IntegrationHealthController::class, 'sync'])
                    ->name('admin.integrations.sync')
                    ->middleware('can:admin.integrations.view');

                Route::get('/quick-links', [QuickLinkAdminController::class, 'index'])
                    ->name('admin.quick-links')
                    ->middleware('can:admin.quick_links.manage');

                Route::get('/settings', fn () => view('admin-module::settings'))
                    ->name('admin.settings')
                    ->middleware('can:admin.settings.manage');

                Route::post('/settings/logo', [BrandingUploadController::class, 'uploadLogo'])
                    ->name('admin.settings.logo')
                    ->middleware('can:admin.settings.manage');

                Route::delete('/settings/logo', [BrandingUploadController::class, 'removeLogo'])
                    ->name('admin.settings.logo.remove')
                    ->middleware('can:admin.settings.manage');

                Route::post('/settings/favicon', [BrandingUploadController::class, 'uploadFavicon'])
                    ->name('admin.settings.favicon')
                    ->middleware('can:admin.settings.manage');

                Route::delete('/settings/favicon', [BrandingUploadController::class, 'removeFavicon'])
                    ->name('admin.settings.favicon.remove')
                    ->middleware('can:admin.settings.manage');

                Route::get('/compliance', [ComplianceController::class, 'subjectAccessForm'])
                    ->name('admin.compliance')
                    ->middleware('can:admin.compliance.export');

                Route::post('/compliance/subject-access', [ComplianceController::class, 'subjectAccessExport'])
                    ->name('admin.compliance.subject-access.export')
                    ->middleware('can:admin.compliance.export');

                Route::get('/compliance/audit.csv', [ComplianceController::class, 'auditExport'])
                    ->name('admin.compliance.audit-export')
                    ->middleware('can:admin.compliance.export');
            });
    }
}
