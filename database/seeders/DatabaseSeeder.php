<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DirectorySeeder::class,
            Phase2Seeder::class,
            Phase3Seeder::class,
            Phase4Seeder::class,
        ]);
    }
}
