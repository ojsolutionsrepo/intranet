<?php

use App\Models\User;
use App\Modules\Documents\Models\Document;
use App\Modules\Projects\Models\Project;
use App\Shared\Contracts\VirusScanner;
use App\Shared\Security\NullVirusScanner;
use App\Shared\Services\Settings;
use App\Shared\Services\SubjectAccessExporter;
use Database\Seeders\DirectorySeeder;
use Database\Seeders\Phase2Seeder;
use Database\Seeders\Phase3Seeder;
use Database\Seeders\Phase4Seeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\seed;

beforeEach(function () {
    Storage::fake('local');
    seed(RoleSeeder::class);
    seed(DirectorySeeder::class);
    seed(Phase2Seeder::class);
    seed(Phase3Seeder::class);
    seed(Phase4Seeder::class);
});

it('Gate 5A sends security headers on authenticated pages', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('IDOR suite blocks cross-audience document news and project access', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    $jasmine = User::query()->where('email', 'jasmine@oj.local')->firstOrFail();

    $restrictedDoc = Document::query()->where('title', 'Remote Working Policy')->firstOrFail();
    $opsOnly = User::factory()->create(['email' => 'idor.ops@oj.local']);
    $opsOnly->assignRole('Staff');
    $opsOnly->givePermissionTo('documents.view');
    $ops = \App\Shared\Models\Department::query()->where('slug', 'operations')->firstOrFail();
    $opsOnly->departments()->sync([$ops->id => ['job_title' => 'Ops', 'is_lead' => false]]);

    actingAs($opsOnly)
        ->get(route('documents.show', $restrictedDoc))
        ->assertForbidden();

    actingAs($opsOnly)
        ->get(route('documents.download', $restrictedDoc))
        ->assertForbidden();

    $engProject = Project::query()->where('external_ref', 'plane-eng-only')->firstOrFail();
    actingAs($jasmine)
        ->get(route('projects.show', $engProject))
        ->assertForbidden();

    actingAs($staff)
        ->get(route('projects.show', $engProject))
        ->assertOk();

    actingAs($staff)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('virus scanner blocks malware test signature', function () {
    $scanner = app(VirusScanner::class);
    expect($scanner)->toBeInstanceOf(NullVirusScanner::class);

    $path = tempnam(sys_get_temp_dir(), 'ojscan');
    file_put_contents($path, 'safe-prefix OJ-INTRANET-VIRUS-TEST-SIGNATURE safe-suffix');
    $result = $scanner->scan($path);
    @unlink($path);

    expect($result['clean'])->toBeFalse()
        ->and($result['signature'])->toBe('OJ-TEST');

    $cleanPath = tempnam(sys_get_temp_dir(), 'ojscan');
    file_put_contents($cleanPath, 'hello world');
    expect($scanner->scan($cleanPath)['clean'])->toBeTrue();
    @unlink($cleanPath);
});

it('privacy notice and subject-access export work for admin', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    actingAs($staff)
        ->get(route('privacy.notice'))
        ->assertOk()
        ->assertSee('Privacy notice');

    actingAs($admin)
        ->get(route('admin.compliance'))
        ->assertOk()
        ->assertSee('Subject-access');

    $path = app(SubjectAccessExporter::class)->export($staff, $admin);
    expect(Storage::disk('local')->exists($path))->toBeTrue();

    actingAs($admin)
        ->get(route('admin.compliance.audit-export'))
        ->assertOk();
});

it('admin can update site settings', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();

    Livewire\Livewire::actingAs($admin)
        ->test(\App\Modules\Admin\Livewire\SiteSettingsForm::class)
        ->set('site_name', 'OJ Portal')
        ->set('session_idle_timeout', 240)
        ->set('privacy_contact', 'dpo@oj.local')
        ->call('save')
        ->assertHasNoErrors();

    expect(app(Settings::class)->get('site_name'))->toBe('OJ Portal')
        ->and((int) app(Settings::class)->get('session_idle_timeout'))->toBe(240);
});

it('gdpr prune command runs dry-run', function () {
    artisan('intranet:gdpr-prune', ['--dry-run' => true])
        ->assertSuccessful();
});
