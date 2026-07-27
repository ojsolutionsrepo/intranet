<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Documents\Models\DocumentCategory;
use App\Modules\News\Models\Post;
use App\Modules\News\Services\NewsService;
use App\Shared\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Modules\Documents\Services\DocumentService;

class Phase2Seeder extends Seeder
{
    public function run(): void
    {
        foreach (['news' => '1.0.0', 'documents' => '1.0.0', 'policies' => '1.0.0'] as $name => $version) {
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

        $roots = [
            ['name' => 'Policies', 'slug' => 'policies', 'order' => 10],
            ['name' => 'Templates', 'slug' => 'templates', 'order' => 20],
            ['name' => 'Guides', 'slug' => 'guides', 'order' => 30],
            ['name' => 'Forms', 'slug' => 'forms', 'order' => 40],
        ];

        foreach ($roots as $root) {
            DocumentCategory::query()->updateOrCreate(
                ['slug' => $root['slug'], 'parent_id' => null],
                ['name' => $root['name'], 'visibility' => 'all', 'order' => $root['order']],
            );
        }

        $policies = DocumentCategory::query()->where('slug', 'policies')->whereNull('parent_id')->first();
        DocumentCategory::query()->updateOrCreate(
            ['slug' => 'hr-policies', 'parent_id' => $policies?->id],
            ['name' => 'HR Policies', 'visibility' => 'all', 'order' => 1],
        );

        $admin = User::query()->where('email', 'admin@oj.local')->first();
        $engineering = Department::query()->where('slug', 'engineering')->first();
        $people = Department::query()->where('slug', 'people')->first();

        if ($admin) {
            $news = app(NewsService::class);

            if (! Post::query()->where('title', 'Welcome to the intranet news feed')->exists()) {
                $news->create($admin, [
                    'title' => 'Welcome to the intranet news feed',
                    'summary' => 'Company-wide announcements now live here.',
                    'body_html' => '<p>This replaces scattered email announcements. Pin critical posts and target departments as needed.</p>',
                    'category' => 'General',
                    'status' => Post::STATUS_PUBLISHED,
                    'is_pinned' => true,
                    'is_alert' => true,
                    'audience' => [],
                ]);
            }

            if ($engineering && ! Post::query()->where('title', 'Engineering tooling update')->exists()) {
                $news->create($admin, [
                    'title' => 'Engineering tooling update',
                    'summary' => 'Visible only to Engineering.',
                    'body_html' => '<p>Platform CI runners move to the new pool next Monday.</p><script>alert("xss")</script>',
                    'category' => 'Engineering',
                    'status' => Post::STATUS_PUBLISHED,
                    'audience' => ['departments' => [$engineering->id]],
                ]);
            }

            $hrCat = DocumentCategory::query()->where('slug', 'hr-policies')->first();
            if ($hrCat && $people) {
                $path = sys_get_temp_dir().'/remote-working-policy.txt';
                file_put_contents($path, "Remote Working Policy\n\nStaff may work remotely up to three days per week with manager approval.\n");
                $file = new UploadedFile($path, 'remote-working-policy.txt', 'text/plain', null, true);

                $existing = \App\Modules\Documents\Models\Document::query()
                    ->where('title', 'Remote Working Policy')
                    ->first();

                if (! $existing) {
                    app(DocumentService::class)->upload($admin, [
                        'title' => 'Remote Working Policy',
                        'category_id' => $hrCat->id,
                        'visibility' => 'department',
                        'audience' => ['departments' => [$people->id, $engineering->id]],
                        'is_policy' => true,
                        'mandatory_ack' => true,
                        'review_at' => now()->addDays(20)->toDateString(),
                        'changelog' => 'Initial published policy',
                    ], $file);
                }

                @unlink($path);
            }
        }
    }
}
