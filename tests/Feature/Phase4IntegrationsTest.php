<?php

use App\Models\User;
use App\Modules\Projects\Models\Project;
use App\Modules\Projects\Services\ProjectSyncService;
use App\Shared\Adapters\Plane\PlaneFakeDriver;
use App\Shared\Adapters\Sso\JwtSsoDriver;
use App\Shared\Contracts\DriveBroker;
use App\Shared\Contracts\PlaneAdapter;
use App\Shared\Models\IntegrationHealth;
use Database\Seeders\DirectorySeeder;
use Database\Seeders\Phase2Seeder;
use Database\Seeders\Phase3Seeder;
use Database\Seeders\Phase4Seeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\seed;

beforeEach(function () {
    Storage::fake('local');
    config(['integrations.sso.jwt_secret' => 'test-sso-secret-phase4']);
    app()->forgetInstance(JwtSsoDriver::class);
    app()->singleton(JwtSsoDriver::class, fn () => new JwtSsoDriver('test-sso-secret-phase4'));

    seed(RoleSeeder::class);
    seed(DirectorySeeder::class);
    seed(Phase2Seeder::class);
    seed(Phase3Seeder::class);
    seed(Phase4Seeder::class);
});

it('UR-PRJ-01 lists active projects with RAG and synced_at', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    actingAs($staff)
        ->get(route('projects.index'))
        ->assertOk()
        ->assertSee('Projects')
        ->assertSee('Intranet Portal')
        ->assertSee('GREEN')
        ->assertSee('Stale');
});

it('UR-PRJ-02 shows milestones metrics and deep link', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    $project = Project::query()->where('external_ref', 'plane-intranet')->firstOrFail();

    actingAs($staff)
        ->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('Gate 4 integrations')
        ->assertSee('progress')
        ->assertSee('Open in source');
});

it('UR-PRJ-03 returns 403 when project audience excludes the user', function () {
    $jasmine = User::query()->where('email', 'jasmine@oj.local')->firstOrFail(); // People
    $project = Project::query()->where('external_ref', 'plane-eng-only')->firstOrFail();

    expect($project->isVisibleTo($jasmine))->toBeFalse();

    actingAs($jasmine)
        ->get(route('projects.show', $project))
        ->assertForbidden();
});

it('UR-PRJ-04 flags staleness when synced_at older than 60 minutes', function () {
    $project = Project::query()->where('external_ref', 'plane-intranet')->firstOrFail();
    expect($project->isStale())->toBeTrue();

    $project->update(['synced_at' => now()]);
    expect($project->fresh()->isStale())->toBeFalse();
});

it('UR-INT-01 JWT SSO logs in and local login still works when SSO fails', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    $jwt = app(JwtSsoDriver::class);
    $token = $jwt->issueToken($staff->email, $staff->name, 60);

    get(route('sso.callback', ['provider' => 'jwt', 'token' => $token]))
        ->assertRedirect(route('dashboard'));

    assertAuthenticatedAs($staff);

    // Replay jti fails; local login remains.
    post('/logout');
    get(route('sso.callback', ['provider' => 'jwt', 'token' => $token]))
        ->assertRedirect(route('login'));

    post('/login', [
        'email' => 'staff@oj.local',
        'password' => 'password',
    ])->assertRedirect();

    assertAuthenticated();
});

it('UR-INT-02 Drive broker serves cached bytes when Drive is down', function () {
    config(['integrations.drive.enabled' => false]);
    $broker = app(DriveBroker::class);
    $checksum = hash('sha256', 'hello-drive');

    if (method_exists($broker, 'putCache')) {
        $broker->putCache($checksum, 'hello-drive');
    } else {
        Storage::disk('local')->put('drive-cache/'.$checksum, 'hello-drive');
    }

    $resolved = $broker->resolve('file-1', 'rev-1', $checksum);
    expect($resolved['available'])->toBeTrue()
        ->and($resolved['cached'])->toBeTrue();

    $miss = $broker->resolve('file-2', 'rev-1', hash('sha256', 'missing'));
    expect($miss['available'])->toBeFalse()
        ->and($miss['message'])->toContain('unavailable');
});

it('UR-INT-03 Plane sync upserts projects into the local mirror', function () {
    /** @var PlaneFakeDriver $fake */
    $fake = app(PlaneAdapter::class);
    expect($fake)->toBeInstanceOf(PlaneFakeDriver::class);
    $fake->setProjects([
        [
            'external_ref' => 'plane-new',
            'name' => 'Fresh Plane Project',
            'status' => 'active',
            'summary' => 'Synced now',
            'rag' => 'green',
            'deep_link' => 'https://plane.example/projects/plane-new',
            'metrics' => [],
            'milestones' => [],
            'audience' => [],
        ],
    ]);

    $result = app(ProjectSyncService::class)->syncAll();
    expect($result['plane'])->toBeGreaterThanOrEqual(1)
        ->and(Project::query()->where('external_ref', 'plane-new')->exists())->toBeTrue();
});

it('UR-INT-05 core stays usable when Plane fails with clear staleness', function () {
    /** @var PlaneFakeDriver $fake */
    $fake = app(PlaneAdapter::class);
    $fake->fail(true);

    $result = app(ProjectSyncService::class)->syncAll();
    expect($result['errors'])->not->toBeEmpty();

    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    actingAs($staff)
        ->get(route('projects.index'))
        ->assertOk()
        ->assertSee('Intranet Portal');

    expect(IntegrationHealth::query()->where('name', 'plane')->value('status'))->toBe('down');
});

it('UR-INT-06 admin integration health page and Sync now', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();

    actingAs($admin)
        ->get(route('admin.integrations'))
        ->assertOk()
        ->assertSee('Integration health')
        ->assertSee('Sync now');

    actingAs($admin)
        ->post(route('admin.integrations.sync'))
        ->assertRedirect();

    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    actingAs($staff)
        ->get(route('admin.integrations'))
        ->assertForbidden();
});

it('dashboard My Projects widget renders seeded projects', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('My Projects')
        ->assertSee('Intranet Portal');
});

it('dashboard shows email Zenzap and platform quick links for staff', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Quick Links')
        ->assertSee('Company email')
        ->assertSee('Zenzap')
        ->assertSee('Google Drive')
        ->assertSee('Plane.so');
});

it('admin can add a manual project that appears on the staff dashboard', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    actingAs($admin)
        ->get(route('projects.create'))
        ->assertOk()
        ->assertSee('Add project');

    Livewire\Livewire::actingAs($admin)
        ->test(\App\Modules\Projects\Livewire\ProjectForm::class)
        ->set('name', 'Board pack Q3')
        ->set('summary', 'Admin-published project')
        ->set('rag', 'amber')
        ->set('deep_link', 'https://drive.google.com/drive/folders/demo')
        ->call('save')
        ->assertHasNoErrors();

    $project = Project::query()->where('name', 'Board pack Q3')->firstOrFail();
    expect($project->source)->toBe('manual');

    actingAs($staff)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Board pack Q3');
});

it('admin can manage quick links and Drive connect UI is present', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();

    actingAs($admin)
        ->get(route('admin.quick-links'))
        ->assertOk()
        ->assertSee('Quick links');

    actingAs($admin)
        ->get(route('admin.integrations'))
        ->assertOk()
        ->assertSee('Google Drive')
        ->assertSee('GOOGLE_DRIVE_CLIENT_ID')
        ->assertSee('OAuth credentials')
        ->assertSee('Save to .env')
        ->assertSee(route('drive.oauth.callback'));

    expect(app(DriveBroker::class)->isConnected())->toBeFalse();
    expect(app(DriveBroker::class)->configured())->toBeFalse();
});

it('admin can save Drive OAuth credentials via GUI', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();

    Livewire\Livewire::actingAs($admin)
        ->test(\App\Modules\Admin\Livewire\DriveCredentialsForm::class)
        ->set('client_id', 'gui-client.apps.googleusercontent.com')
        ->set('client_secret', 'gui-client-secret')
        ->set('folder_id', 'folder-abc')
        ->set('enabled', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.integrations'));

    expect(config('integrations.drive.enabled'))->toBeTrue();
    expect(config('integrations.drive.client_id'))->toBe('gui-client.apps.googleusercontent.com');
    expect(config('integrations.drive.client_secret'))->toBe('gui-client-secret');
    expect(config('integrations.drive.folder_id'))->toBe('folder-abc');

    app()->forgetInstance(DriveBroker::class);
    expect(app(DriveBroker::class)->configured())->toBeTrue();
    expect(app(DriveBroker::class)->name())->toBe('google_drive_oauth');
});

it('Drive credentials form rejects enable without client id/secret', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();

    Livewire\Livewire::actingAs($admin)
        ->test(\App\Modules\Admin\Livewire\DriveCredentialsForm::class)
        ->set('client_id', '')
        ->set('client_secret', '')
        ->set('enabled', true)
        ->call('save')
        ->assertHasErrors(['enabled']);
});

it('Installer writeEnv updates Drive keys on a target file', function () {
    $path = storage_path('framework/testing/drive-env-'.uniqid('', true));
    file_put_contents($path, "APP_NAME=Intranet\nDRIVE_BROKER_ENABLED=false\nGOOGLE_DRIVE_CLIENT_ID=\n");

    app(\App\Shared\Services\Installer::class)->writeEnv([
        'DRIVE_BROKER_ENABLED' => 'true',
        'GOOGLE_DRIVE_CLIENT_ID' => 'from-gui.apps.googleusercontent.com',
        'GOOGLE_DRIVE_CLIENT_SECRET' => 'secret-from-gui',
        'GOOGLE_DRIVE_FOLDER_ID' => 'folder-1',
    ], $path);

    $content = file_get_contents($path);
    expect($content)->toContain('DRIVE_BROKER_ENABLED=true')
        ->and($content)->toContain('GOOGLE_DRIVE_CLIENT_ID=from-gui.apps.googleusercontent.com')
        ->and($content)->toContain('GOOGLE_DRIVE_CLIENT_SECRET=secret-from-gui')
        ->and($content)->toContain('GOOGLE_DRIVE_FOLDER_ID=folder-1');

    @unlink($path);
});

it('Drive OAuth redirect warns when credentials are missing', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();

    actingAs($admin)
        ->get(route('drive.oauth.redirect'))
        ->assertRedirect(route('admin.integrations'))
        ->assertSessionHas('warning');
});

it('Drive OAuth redirect sends admin to Google when configured', function () {
    config([
        'integrations.drive.enabled' => true,
        'integrations.drive.client_id' => 'test-client-id.apps.googleusercontent.com',
        'integrations.drive.client_secret' => 'test-client-secret',
    ]);
    app()->forgetInstance(DriveBroker::class);

    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();

    $response = actingAs($admin)->get(route('drive.oauth.redirect'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toStartWith('https://accounts.google.com/o/oauth2/v2/auth');
    expect(session('drive_oauth_state'))->not->toBeEmpty();
});
