<?php

namespace App\Modules\Documents;

use App\Core\Modules\ModuleRegistry;
use App\Modules\Documents\Contracts\DocumentStorageAdapter;
use App\Modules\Documents\Http\Controllers\DocumentController;
use App\Modules\Documents\Http\Controllers\PolicyController;
use App\Modules\Documents\Livewire\DocumentBrowser;
use App\Modules\Documents\Livewire\DocumentUpload;
use App\Modules\Documents\Livewire\PolicyHub;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Policies\DocumentPolicy;
use App\Modules\Documents\Storage\LocalStorageDriver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class DocumentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DocumentStorageAdapter::class, fn () => new LocalStorageDriver);
    }

    public function boot(ModuleRegistry $registry): void
    {
        $registry->register('documents')
            ->permissions([
                'documents.view' => 'Browse and download documents',
                'documents.upload' => 'Upload and version documents',
                'documents.manage' => 'Manage categories, trash, and ACL',
                'policies.view' => 'View the policy hub',
                'policies.manage' => 'Manage policies and compliance exports',
            ])
            ->menu(fn () => [
                'label' => 'Documents',
                'icon' => 'folder',
                'route' => 'documents.index',
                'permission' => 'documents.view',
                'order' => 30,
            ])
            ->views(__DIR__.'/Resources/views', 'documents');

        View::addNamespace('documents', __DIR__.'/Resources/views');
        Gate::policy(Document::class, DocumentPolicy::class);

        Livewire::component('documents.browser', DocumentBrowser::class);
        Livewire::component('documents.upload', DocumentUpload::class);
        Livewire::component('documents.policy-hub', PolicyHub::class);

        Route::middleware(['web', 'auth', 'session.active'])
            ->group(function () use ($registry): void {
                Route::prefix('documents')->group(function () {
                    Route::get('/', [DocumentController::class, 'index'])
                        ->name('documents.index')
                        ->middleware('can:documents.view');

                    Route::get('/upload', [DocumentController::class, 'upload'])
                        ->name('documents.upload')
                        ->middleware('can:documents.upload');

                    Route::get('/search', [DocumentController::class, 'search'])
                        ->name('documents.search')
                        ->middleware('can:documents.view');

                    Route::get('/storage', [DocumentController::class, 'storage'])
                        ->name('documents.storage')
                        ->middleware('signed');

                    Route::get('/{document}/download', [DocumentController::class, 'download'])
                        ->name('documents.download')
                        ->middleware('can:documents.view');

                    Route::get('/{document}/versions/{version}/download', [DocumentController::class, 'downloadVersion'])
                        ->name('documents.download-version')
                        ->middleware('can:documents.view');

                    Route::post('/{document}/versions/{version}/restore', [DocumentController::class, 'restoreVersion'])
                        ->name('documents.restore-version')
                        ->middleware('can:documents.upload');

                    Route::get('/{document}', [DocumentController::class, 'show'])
                        ->name('documents.show')
                        ->middleware('can:documents.view');
                });

                Route::prefix('policies')->group(function () {
                    Route::get('/', [PolicyController::class, 'index'])
                        ->name('policies.index')
                        ->middleware('can:policies.view');

                    Route::post('/{document}/acknowledge', [PolicyController::class, 'acknowledge'])
                        ->name('policies.acknowledge')
                        ->middleware('can:policies.view');

                    Route::get('/{document}/compliance.csv', [PolicyController::class, 'exportCompliance'])
                        ->name('policies.compliance')
                        ->middleware('can:policies.manage');
                });
            });
    }
}
