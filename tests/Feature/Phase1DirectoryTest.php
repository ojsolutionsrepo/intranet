<?php

use App\Models\User;
use App\Modules\Admin\Services\UserAdminService;
use App\Modules\Directory\Services\DirectorySearch;
use App\Modules\Directory\Services\StaffImportService;
use App\Shared\Models\AuditLog;
use App\Shared\Models\Department;
use Database\Seeders\DirectorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(DirectorySeeder::class);
});

it('UR-DIR-01 browses departments and sub-teams', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    $engineering = Department::query()->where('slug', 'engineering')->firstOrFail();

    $this->actingAs($staff)
        ->get(route('directory.department', $engineering))
        ->assertOk()
        ->assertSee('Engineering')
        ->assertSee('Platform');
});

it('UR-DIR-02 finds people by name with typo tolerance', function () {
    $search = app(DirectorySearch::class);

    $exact = $search->search(['q' => 'Jasmine']);
    expect($exact->total())->toBeGreaterThan(0);

    $typo = $search->search(['q' => 'Jasmin']);
    expect($typo->total())->toBeGreaterThan(0)
        ->and($search->fuzzyMatch('Jasmine Okonkwo', 'Jasmin'))->toBeTrue();
});

it('UR-DIR-03 combines department role and expertise filters', function () {
    $search = app(DirectorySearch::class);
    $engineering = Department::query()->where('slug', 'engineering')->firstOrFail();

    $results = $search->search([
        'department_id' => $engineering->id,
        'role' => 'Staff',
        'expertise' => 'Laravel',
    ]);

    expect($results->total())->toBeGreaterThan(0);
    foreach ($results as $user) {
        expect($user->departments->contains('id', $engineering->id))->toBeTrue()
            ->and($user->hasRole('Staff'))->toBeTrue()
            ->and(collect($user->expertiseTags())->contains(fn ($t) => stripos($t, 'Laravel') !== false))->toBeTrue();
    }
});

it('UR-DIR-04 shows a full staff profile', function () {
    $viewer = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    $jasmine = User::query()->where('email', 'jasmine@oj.local')->firstOrFail();

    $this->actingAs($viewer)
        ->get(route('directory.show', $jasmine))
        ->assertOk()
        ->assertSee('Jasmine Okonkwo')
        ->assertSee('jasmine@oj.local')
        ->assertSee('301')
        ->assertSee('People Partner')
        ->assertSee('Onboarding')
        ->assertSee('Remote — UK');

    expect(AuditLog::query()->where('action', 'directory.profile_viewed')->exists())->toBeTrue();
});

it('UR-DIR-05 shows department page content', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    $people = Department::query()->where('slug', 'people')->firstOrFail();

    $this->actingAs($staff)
        ->get(route('directory.department', $people))
        ->assertOk()
        ->assertSee('People')
        ->assertSee('Talent')
        ->assertSee('Jasmine Okonkwo');
});

it('UR-DIR-06 allows self-edit of profile fields only', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    Livewire::actingAs($staff)
        ->test(\App\Modules\Directory\Livewire\ProfileEdit::class)
        ->set('bio', 'Updated bio for Phase 1')
        ->set('phone', '+44 20 1111 2222')
        ->set('location', 'Manchester')
        ->set('expertiseInput', 'PHP, Testing')
        ->call('save')
        ->assertHasNoErrors();

    $staff->refresh()->load('profile');
    expect($staff->profile->bio)->toBe('Updated bio for Phase 1')
        ->and($staff->profile->phone)->toBe('+44 20 1111 2222')
        ->and($staff->profile->location)->toBe('Manchester')
        ->and($staff->expertiseTags())->toContain('PHP', 'Testing');

    expect(AuditLog::query()->where('action', 'profile.updated')->exists())->toBeTrue();
});

it('UR-DIR-06 forbids staff from admin user edit routes', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    $target = User::query()->where('email', 'jasmine@oj.local')->firstOrFail();

    $this->actingAs($staff)
        ->get(route('admin.users.edit', $target))
        ->assertForbidden();
});

it('UR-DIR-09 previews import and rejects bad rows without committing them', function () {
    $csv = implode("\n", [
        'name,email,department,role',
        'Valid Person,valid.person@oj.local,Engineering,Staff',
        ',bad@oj.local,Engineering,Staff',
        'No Dept,nodept@oj.local,,Staff',
        'Bad Role,badrole@oj.local,Engineering,SuperUser',
    ]);

    $path = sys_get_temp_dir().'/staff-import-'.uniqid().'.csv';
    file_put_contents($path, $csv);
    $file = new UploadedFile($path, 'staff.csv', 'text/csv', null, true);

    $importer = app(StaffImportService::class);
    $preview = $importer->preview($file);

    expect($preview['errors'])->not->toBeEmpty();
    expect(collect($preview['rows'])->where('_valid', true)->count())->toBe(1);
    expect(collect($preview['rows'])->where('_valid', false)->count())->toBe(3);

    $stats = $importer->commit($preview['rows']);
    expect($stats['created'])->toBe(1)
        ->and($stats['skipped'])->toBe(3)
        ->and(User::query()->where('email', 'valid.person@oj.local')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'badrole@oj.local')->exists())->toBeFalse();

    @unlink($path);
});

it('ensures seeded staff belong to a department', function () {
    $withoutDept = User::query()
        ->where('is_active', true)
        ->whereDoesntHave('departments')
        ->count();

    expect($withoutDept)->toBe(0);
});

it('ADM-01 deactivates a user and clears their session', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    DB::table('sessions')->insert([
        'id' => 'test-session-staff',
        'user_id' => $staff->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'x',
        'last_activity' => time(),
    ]);

    app(UserAdminService::class)->deactivate($staff);

    expect($staff->fresh()->is_active)->toBeFalse()
        ->and(DB::table('sessions')->where('user_id', $staff->id)->count())->toBe(0)
        ->and(AuditLog::query()->where('action', 'user.deactivated')->exists())->toBeTrue();

    $this->actingAs($staff->fresh())
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

it('ADM-02 saves the permission matrix with immediate effect', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    expect($staff->can('directory.import'))->toBeFalse();

    Livewire::actingAs($admin)
        ->test(\App\Modules\Admin\Livewire\PermissionMatrix::class)
        ->call('toggle', 'Staff', 'directory.import')
        ->call('save')
        ->assertHasNoErrors();

    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $staff->refresh();
    $staff->unsetRelation('roles');
    $staff->unsetRelation('permissions');

    expect($staff->can('directory.import'))->toBeTrue()
        ->and(AuditLog::query()->where('action', 'permissions.matrix_updated')->exists())->toBeTrue();
});

it('lists the directory for authenticated staff', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    $this->actingAs($staff)
        ->get(route('directory.index'))
        ->assertOk()
        ->assertSee('Directory')
        ->assertSee('Jasmine Okonkwo');
});
