<?php

namespace App\Modules\Admin\Livewire;

use App\Core\Modules\ModuleRegistry;
use App\Shared\Services\AuditLogger;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionMatrix extends Component
{
    /**
     * Flat map keyed without dots so Livewire wire:model paths stay stable.
     *
     * @var array<string, bool>
     */
    public array $matrix = [];

    /** @var list<string> */
    public array $permissionNames = [];

    /** @var list<string> */
    public array $roleNames = [];

    public function mount(ModuleRegistry $registry): void
    {
        $this->permissionNames = array_keys($registry->allPermissions());
        sort($this->permissionNames);

        $this->roleNames = Role::query()->orderBy('name')->pluck('name')->all();

        foreach ($this->roleNames as $roleName) {
            $role = Role::findByName($roleName);
            $assigned = $role->permissions->pluck('name')->all();
            foreach ($this->permissionNames as $permission) {
                $this->matrix[$this->key($roleName, $permission)] = in_array($permission, $assigned, true);
            }
        }
    }

    public function toggle(string $role, string $permission): void
    {
        $key = $this->key($role, $permission);
        $this->matrix[$key] = ! ($this->matrix[$key] ?? false);
    }

    public function save(AuditLogger $audit): void
    {
        foreach ($this->roleNames as $roleName) {
            $role = Role::findByName($roleName);
            $selected = [];

            foreach ($this->permissionNames as $permission) {
                if ($this->matrix[$this->key($roleName, $permission)] ?? false) {
                    Permission::findOrCreate($permission);
                    $selected[] = $permission;
                }
            }

            $role->syncPermissions($selected);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $audit->log('permissions.matrix_updated', null, null, ['roles' => $this->roleNames]);

        session()->flash('status', 'Permission matrix saved. Changes take effect immediately.');
    }

    public function key(string $role, string $permission): string
    {
        return $role.'__'.str_replace('.', '_', $permission);
    }

    public function render()
    {
        return view('admin-module::livewire.permission-matrix');
    }
}
