<?php

namespace Database\Seeders;

use App\Models\User;
use App\Shared\Models\Department;
use App\Shared\Models\Team;
use App\Shared\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DirectorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('modules')->updateOrInsert(
            ['name' => 'directory'],
            [
                'version' => '1.0.0',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('modules')->updateOrInsert(
            ['name' => 'admin'],
            [
                'version' => '1.0.0',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $engineering = Department::query()->updateOrCreate(
            ['slug' => 'engineering'],
            [
                'name' => 'Engineering',
                'description' => 'Product engineering and platform.',
                'order' => 10,
            ],
        );

        $people = Department::query()->updateOrCreate(
            ['slug' => 'people'],
            [
                'name' => 'People',
                'description' => 'HR, culture, and people operations.',
                'order' => 20,
            ],
        );

        $ops = Department::query()->updateOrCreate(
            ['slug' => 'operations'],
            [
                'name' => 'Operations',
                'description' => 'Delivery and client operations.',
                'order' => 30,
            ],
        );

        $platform = Team::query()->updateOrCreate(
            ['department_id' => $engineering->id, 'slug' => 'platform'],
            ['name' => 'Platform', 'description' => 'Core platform and infrastructure.'],
        );

        $talent = Team::query()->updateOrCreate(
            ['department_id' => $people->id, 'slug' => 'talent'],
            ['name' => 'Talent', 'description' => 'Recruiting and onboarding.'],
        );

        $admin = User::query()->where('email', 'admin@oj.local')->first();
        $staff = User::query()->where('email', 'staff@oj.local')->first();

        $manager = User::query()->updateOrCreate(
            ['email' => 'manager@oj.local'],
            [
                'name' => 'Maya Manager',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );
        $manager->syncRoles(['Manager']);

        $jasmine = User::query()->updateOrCreate(
            ['email' => 'jasmine@oj.local'],
            [
                'name' => 'Jasmine Okonkwo',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
                'manager_id' => $manager->id,
            ],
        );
        $jasmine->syncRoles(['Staff']);

        $ade = User::query()->updateOrCreate(
            ['email' => 'ade@oj.local'],
            [
                'name' => 'Ade Bakare',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
                'manager_id' => $manager->id,
            ],
        );
        $ade->syncRoles(['Staff']);

        if ($admin) {
            $admin->update(['manager_id' => null]);
            $this->attach($admin, $ops, null, 'System Administrator', true);
            UserProfile::query()->updateOrCreate(
                ['user_id' => $admin->id],
                [
                    'phone' => '+44 20 7946 0001',
                    'extension' => '100',
                    'location' => 'London HQ',
                    'bio' => 'Keeps the intranet running.',
                    'expertise' => ['Security', 'Access', 'Operations'],
                    'start_date' => '2020-01-15',
                ],
            );
            $ops->update(['lead_user_id' => $admin->id]);
        }

        if ($staff) {
            $staff->update(['manager_id' => $manager->id]);
            $this->attach($staff, $engineering, $platform, 'Software Engineer');
            UserProfile::query()->updateOrCreate(
                ['user_id' => $staff->id],
                [
                    'phone' => '+44 20 7946 0101',
                    'extension' => '201',
                    'location' => 'London HQ',
                    'bio' => 'Builds internal tools.',
                    'expertise' => ['PHP', 'Laravel', 'Livewire'],
                    'start_date' => '2023-03-01',
                ],
            );
        }

        $this->attach($manager, $engineering, $platform, 'Engineering Manager', true);
        UserProfile::query()->updateOrCreate(
            ['user_id' => $manager->id],
            [
                'phone' => '+44 20 7946 0200',
                'extension' => '200',
                'location' => 'London HQ',
                'bio' => 'Leads the Engineering department.',
                'expertise' => ['Leadership', 'Architecture', 'Mentoring'],
                'start_date' => '2021-06-01',
            ],
        );
        $engineering->update(['lead_user_id' => $manager->id]);

        $this->attach($jasmine, $people, $talent, 'People Partner');
        UserProfile::query()->updateOrCreate(
            ['user_id' => $jasmine->id],
            [
                'phone' => '+44 20 7946 0301',
                'extension' => '301',
                'location' => 'Remote — UK',
                'bio' => 'Helps colleagues find the right people and policies.',
                'expertise' => ['Onboarding', 'Culture', 'HRIS'],
                'start_date' => '2022-09-12',
            ],
        );
        $people->update(['lead_user_id' => $jasmine->id]);

        $this->attach($ade, $engineering, $platform, 'Platform Engineer');
        UserProfile::query()->updateOrCreate(
            ['user_id' => $ade->id],
            [
                'phone' => '+44 20 7946 0202',
                'extension' => '202',
                'location' => 'London HQ',
                'bio' => 'Owns CI and developer experience.',
                'expertise' => ['DevOps', 'CI', 'Laravel'],
                'start_date' => '2024-01-08',
            ],
        );
    }

    private function attach(User $user, Department $department, ?Team $team, string $title, bool $isLead = false): void
    {
        $user->departments()->sync([
            $department->id => [
                'is_lead' => $isLead,
                'job_title' => $title,
            ],
        ]);

        if ($team) {
            $user->teams()->sync([$team->id]);
        }
    }
}
