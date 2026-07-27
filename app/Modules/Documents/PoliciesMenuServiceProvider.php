<?php

namespace App\Modules\Documents;

use App\Core\Modules\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Thin module registration so Policy hub appears as its own sidebar item
 * while sharing Documents models/services.
 */
class PoliciesMenuServiceProvider extends ServiceProvider
{
    public function boot(ModuleRegistry $registry): void
    {
        $registry->register('policies')
            ->permissions([])
            ->menu(fn () => [
                'label' => 'Policies',
                'icon' => 'shield',
                'route' => 'policies.index',
                'permission' => 'policies.view',
                'order' => 35,
            ]);
    }
}
