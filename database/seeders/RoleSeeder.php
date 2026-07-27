<?php

namespace Database\Seeders;

use App\Core\Modules\ModuleRegistry;
use App\Core\Modules\ModuleServiceProvider;
use App\Models\User;
use App\Shared\Services\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['Admin', 'Manager', 'Staff', 'Guest'] as $role) {
            Role::findOrCreate($role);
        }

        $registry = app(ModuleRegistry::class);
        ModuleServiceProvider::syncPermissions($registry);

        $permissionNames = array_keys($registry->allPermissions());

        $adminRole = Role::findByName('Admin');
        $adminRole->syncPermissions($permissionNames);

        $managerPermissions = collect($permissionNames)
            ->reject(fn (string $key) => str_starts_with($key, 'admin.'))
            ->all();

        Role::findByName('Manager')->syncPermissions($managerPermissions);
        Role::findByName('Staff')->syncPermissions([
            'demo.view',
            'directory.view',
            'news.view',
            'documents.view',
            'policies.view',
        ]);
        Role::findByName('Guest')->syncPermissions([]);

        DB::table('modules')->updateOrInsert(
            ['name' => 'demo'],
            [
                'version' => '1.0.0',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        app(Settings::class)->set('session_idle_timeout', 480, 'auth');
        app(Settings::class)->set('site_name', 'OJ Intranet', 'branding');

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@oj.local'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles(['Admin']);

        $staff = User::query()->updateOrCreate(
            ['email' => 'staff@oj.local'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
        $staff->syncRoles(['Staff']);
    }
}
