<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Calendar\Services\CalendarService;
use App\Shared\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Phase3Seeder extends Seeder
{
    public function run(): void
    {
        foreach (['dashboard' => '1.0.0', 'calendar' => '1.0.0', 'search' => '1.0.0'] as $name => $version) {
            DB::table('modules')->updateOrInsert(
                ['name' => $name],
                [
                    'version' => $version,
                    'is_enabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        $admin = User::query()->where('email', 'admin@oj.local')->first();
        $engineering = Department::query()->where('slug', 'engineering')->first();
        $people = Department::query()->where('slug', 'people')->first();

        if (! $admin) {
            return;
        }

        $calendar = app(CalendarService::class);

        if (! \App\Modules\Calendar\Models\CalendarEvent::query()->where('title', 'All-hands town hall')->exists()) {
            $calendar->create($admin, [
                'title' => 'All-hands town hall',
                'description' => 'Company-wide update.',
                'category' => 'General',
                'starts_at' => now()->addDays(3)->setTime(15, 0),
                'ends_at' => now()->addDays(3)->setTime(16, 0),
                'location' => 'London HQ / Meet',
                'audience' => [],
            ]);
        }

        if ($engineering && ! \App\Modules\Calendar\Models\CalendarEvent::query()->where('title', 'Engineering sprint review')->exists()) {
            $calendar->create($admin, [
                'title' => 'Engineering sprint review',
                'description' => 'Visible only to Engineering.',
                'category' => 'Department',
                'starts_at' => now()->addDays(2)->setTime(11, 0),
                'ends_at' => now()->addDays(2)->setTime(12, 0),
                'location' => 'Platform room',
                'audience' => ['departments' => [$engineering->id]],
            ]);
        }

        if ($people && ! \App\Modules\Calendar\Models\CalendarEvent::query()->where('title', 'People partner sync')->exists()) {
            $calendar->create($admin, [
                'title' => 'People partner sync',
                'description' => 'HR-only planning.',
                'category' => 'Training',
                'starts_at' => now()->addDays(4)->setTime(10, 0),
                'ends_at' => now()->addDays(4)->setTime(11, 0),
                'audience' => ['departments' => [$people->id]],
            ]);
        }
    }
}
