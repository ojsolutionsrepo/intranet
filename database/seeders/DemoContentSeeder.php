<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo content for the web installer (optional).
 * Assumes roles/permissions already exist from Installer::seedFoundation().
 * Does not overwrite passwords of accounts that already exist.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureDemoUsers();

        $this->call([
            DirectorySeeder::class,
            Phase2Seeder::class,
            Phase3Seeder::class,
            Phase4Seeder::class,
        ]);
    }

    private function ensureDemoUsers(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@oj.local'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
        if (! $admin->hasRole('Admin')) {
            $admin->syncRoles(['Admin']);
        }

        $staff = User::query()->firstOrCreate(
            ['email' => 'staff@oj.local'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
        if (! $staff->hasRole('Staff')) {
            $staff->syncRoles(['Staff']);
        }
    }
}
