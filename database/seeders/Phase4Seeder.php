<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Dashboard\Models\QuickLink;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Models\ProjectMilestone;
use App\Modules\Projects\Services\ProjectSyncService;
use App\Shared\Adapters\Plane\PlaneFakeDriver;
use App\Shared\Contracts\PlaneAdapter;
use App\Shared\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class Phase4Seeder extends Seeder
{
    public function run(): void
    {
        DB::table('modules')->updateOrInsert(
            ['name' => 'projects'],
            [
                'version' => '1.0.0',
                'is_enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $engineering = Department::query()->where('slug', 'engineering')->first();

        $planeProjects = [
            [
                'external_ref' => 'plane-intranet',
                'name' => 'Intranet Portal',
                'status' => 'active',
                'summary' => 'OJ Solutions intranet modular monolith.',
                'rag' => 'green',
                'deep_link' => 'https://plane.example/projects/plane-intranet',
                'metrics' => ['progress' => 72],
                'audience' => [],
                'milestones' => [
                    ['title' => 'Gate 3 complete', 'due_on' => now()->subWeek()->toDateString(), 'status' => 'done'],
                    ['title' => 'Gate 4 integrations', 'due_on' => now()->addWeek()->toDateString(), 'status' => 'in_progress'],
                ],
            ],
            [
                'external_ref' => 'plane-eng-only',
                'name' => 'Platform rewrite',
                'status' => 'active',
                'summary' => 'Engineering-only visibility demo.',
                'rag' => 'amber',
                'deep_link' => 'https://plane.example/projects/plane-eng-only',
                'metrics' => ['progress' => 40],
                'audience' => $engineering ? ['departments' => [$engineering->id]] : [],
                'milestones' => [
                    ['title' => 'Spike', 'due_on' => now()->addDays(10)->toDateString(), 'status' => 'planned'],
                ],
            ],
        ];

        $fake = app(PlaneAdapter::class);
        if ($fake instanceof PlaneFakeDriver) {
            $fake->setProjects($planeProjects);
        }

        app(ProjectSyncService::class)->upsertFromSource('plane', $planeProjects);

        // Stale project for badge demos (synced > 60 min ago).
        Project::query()->where('external_ref', 'plane-intranet')->update([
            'synced_at' => now()->subHours(2),
        ]);

        // Refresh the eng project as fresh.
        Project::query()->where('external_ref', 'plane-eng-only')->update([
            'synced_at' => now(),
        ]);

        if (! Project::query()->where('slug', 'manual-ops-handbook')->exists()) {
            $manual = Project::query()->create([
                'name' => 'Ops handbook refresh',
                'slug' => 'manual-ops-handbook',
                'source' => 'manual',
                'status' => 'active',
                'summary' => 'Manual project — no external sync.',
                'rag' => 'green',
                'audience' => [],
                'metrics' => ['owner' => 'Operations'],
                'synced_at' => null,
            ]);
            ProjectMilestone::query()->create([
                'project_id' => $manual->id,
                'title' => 'Draft outline',
                'due_on' => now()->addMonth()->toDateString(),
                'status' => 'planned',
                'order' => 0,
            ]);
        }

        $csvDir = storage_path('app/integrations');
        File::ensureDirectoryExists($csvDir);
        $csvPath = $csvDir.'/governex.csv';
        if (! is_file($csvPath)) {
            file_put_contents($csvPath, "external_ref,name,status,summary,rag,budget\n".
                "gov-fy26,FY26 Portfolio,active,Governex CSV sample,green,125000\n");
        }

        app(ProjectSyncService::class)->syncAll();

        // Keep intranet project intentionally stale after sync for Gate 4 demos in seed data —
        // re-apply after sync so UI shows the badge.
        Project::query()->where('external_ref', 'plane-intranet')->update([
            'synced_at' => now()->subHours(2),
        ]);

        $this->seedQuickLinks();
    }

    private function seedQuickLinks(): void
    {
        $defaults = [
            [
                'label' => 'Company email',
                'url' => 'https://mail.google.com',
                'category' => 'comms',
                'description' => 'Open Gmail / Workspace mail',
                'sort_order' => 10,
            ],
            [
                'label' => 'Zenzap · General',
                'url' => 'https://app.zenzap.com',
                'category' => 'comms',
                'description' => 'Team chat channel (update URL when tenant is ready)',
                'sort_order' => 20,
            ],
            [
                'label' => 'Google Drive',
                'url' => 'https://drive.google.com',
                'category' => 'platform',
                'description' => 'Shared Drive (SSO via Google account)',
                'sort_order' => 30,
            ],
            [
                'label' => 'Plane.so',
                'url' => config('integrations.plane.base_url') ?: 'https://app.plane.so',
                'category' => 'platform',
                'description' => 'Project tracker SSO',
                'sort_order' => 40,
            ],
            [
                'label' => 'Governex',
                'url' => config('integrations.governex.base_url') ?: 'https://governex.example',
                'category' => 'platform',
                'description' => 'Portfolio / finance (SSO or CSV-backed)',
                'sort_order' => 50,
            ],
            [
                'label' => 'Projects',
                'url' => 'route:projects.index',
                'category' => 'internal',
                'description' => 'Intranet project list (admin + synced)',
                'sort_order' => 60,
                'opens_external' => false,
            ],
            [
                'label' => 'Documents',
                'url' => 'route:documents.index',
                'category' => 'internal',
                'opens_external' => false,
                'sort_order' => 70,
            ],
            [
                'label' => 'Calendar',
                'url' => 'route:calendar.index',
                'category' => 'internal',
                'opens_external' => false,
                'sort_order' => 80,
            ],
        ];

        foreach ($defaults as $row) {
            QuickLink::query()->updateOrCreate(
                ['label' => $row['label']],
                [
                    'url' => $row['url'],
                    'category' => $row['category'],
                    'description' => $row['description'] ?? null,
                    'sort_order' => $row['sort_order'],
                    'opens_external' => $row['opens_external'] ?? true,
                    'staff_visible' => true,
                    'is_active' => true,
                ],
            );
        }
    }
}
