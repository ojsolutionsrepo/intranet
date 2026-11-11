<?php

use App\Models\User;
use App\Modules\Directory\Models\StaffInformation;
use Database\Seeders\DirectorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RoleSeeder::class);
    seed(DirectorySeeder::class);
    Storage::fake('public');
});

it('shows the staff information form to staff', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    actingAs($staff)
        ->get(route('directory.staff-information.edit'))
        ->assertOk()
        ->assertSee('Staff information')
        ->assertSee('Full name')
        ->assertSee('Next of kin')
        ->assertSee('Bank details')
        ->assertSee('CV / Resume');
});

it('saves staff information including encrypted bank details and uploads', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    $photo = UploadedFile::fake()->image('headshot.jpg', 400, 400);
    $cv = UploadedFile::fake()->create('resume.pdf', 120, 'application/pdf');

    actingAs($staff)
        ->post(route('directory.staff-information.update'), [
            'full_name' => 'Staff User',
            'preferred_name' => 'Staff',
            'phone_number' => '+44 7700 900123',
            'personal_email' => 'staff.personal@example.com',
            'gender' => 'prefer_not_to_say',
            'nationality' => 'British',
            'date_of_birth' => '1990-05-12',
            'residential_address' => "1 Example Street\nLondon",
            'nok_full_name' => 'Kin Example',
            'nok_phone' => '+44 7700 900999',
            'nok_address' => '2 Example Street',
            'nok_relationship' => 'Sibling',
            'job_title' => 'Developer',
            'contract_type' => 'full_time',
            'start_date' => '2024-01-15',
            'role_responsibilities' => 'Build and maintain the intranet.',
            'work_email' => 'staff@oj.local',
            'company_phone' => 'ext 101',
            'linkedin_url' => 'https://www.linkedin.com/in/staff-user',
            'bank_name' => 'Example Bank',
            'account_name' => 'Staff User',
            'account_number' => '12345678',
            'sort_code' => '12-34-56',
            'photo' => $photo,
            'cv' => $cv,
        ])
        ->assertRedirect(route('directory.staff-information.edit'));

    $record = StaffInformation::query()->where('user_id', $staff->id)->firstOrFail();

    expect($record->preferred_name)->toBe('Staff')
        ->and($record->contract_type)->toBe('full_time')
        ->and($record->account_number)->toBe('12345678')
        ->and($record->sort_code)->toBe('12-34-56')
        ->and($record->photo_path)->not->toBeNull()
        ->and($record->cv_path)->not->toBeNull()
        ->and($record->cv_original_name)->toBe('resume.pdf')
        ->and($record->submitted_at)->not->toBeNull()
        ->and($record->staff_can_edit)->toBeFalse(); // one submission, then locked

    // A second staff submission is refused until an admin re-opens the form.
    actingAs($staff)
        ->post(route('directory.staff-information.update'), [
            'full_name' => 'Changed Name',
            'phone_number' => '+44 7700 900123',
            'personal_email' => 'staff.personal@example.com',
            'job_title' => 'Developer',
            'contract_type' => 'full_time',
            'start_date' => '2024-01-15',
            'work_email' => 'staff@oj.local',
        ]);
    expect($record->fresh()->full_name)->toBe('Staff User');

    // Encrypted at rest — raw DB value must not equal plaintext.
    $raw = \Illuminate\Support\Facades\DB::table('staff_information')
        ->where('user_id', $staff->id)
        ->value('account_number');
    expect($raw)->not->toBe('12345678');

    expect(Storage::disk('public')->exists($record->photo_path))->toBeTrue()
        ->and(Storage::disk('public')->exists($record->cv_path))->toBeTrue();
});

it('lets admins edit staff information and lock staff editing', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    actingAs($admin)
        ->get(route('admin.users.staff-information.edit', $staff))
        ->assertOk()
        ->assertSee('Staff editing access')
        ->assertSee($staff->name);

    actingAs($admin)
        ->post(route('admin.users.staff-information.update', $staff), [
            'full_name' => 'Staff User',
            'preferred_name' => 'Staff',
            'phone_number' => '+44 7700 900111',
            'personal_email' => 'staff.personal@example.com',
            'job_title' => 'Engineer',
            'contract_type' => 'part_time',
            'start_date' => '2024-02-01',
            'work_email' => 'staff@oj.local',
        ])
        ->assertRedirect(route('admin.users.staff-information.edit', $staff));

    expect(StaffInformation::query()->where('user_id', $staff->id)->value('contract_type'))->toBe('part_time');

    actingAs($admin)
        ->post(route('admin.users.staff-information.editing', $staff), ['staff_can_edit' => '0'])
        ->assertRedirect(route('admin.users.staff-information.edit', $staff));

    expect(StaffInformation::query()->where('user_id', $staff->id)->value('staff_can_edit'))->toBeFalse();

    actingAs($staff)
        ->post(route('directory.staff-information.update'), [
            'full_name' => 'Staff User',
            'phone_number' => '+44 7700 900222',
            'personal_email' => 'staff.personal@example.com',
            'job_title' => 'Hacker',
            'contract_type' => 'full_time',
            'start_date' => '2024-02-01',
            'work_email' => 'staff@oj.local',
        ])
        ->assertForbidden();

    actingAs($admin)
        ->post(route('admin.users.staff-information.update', $staff), [
            'full_name' => 'Staff User',
            'phone_number' => '+44 7700 900333',
            'personal_email' => 'staff.personal@example.com',
            'job_title' => 'Engineer',
            'contract_type' => 'contract',
            'start_date' => '2024-02-01',
            'work_email' => 'staff@oj.local',
        ])
        ->assertRedirect();

    expect(StaffInformation::query()->where('user_id', $staff->id)->value('contract_type'))->toBe('contract');

    actingAs($admin)
        ->post(route('admin.users.staff-information.editing', $staff), ['staff_can_edit' => '1'])
        ->assertRedirect();

    actingAs($staff)
        ->post(route('directory.staff-information.update'), [
            'full_name' => 'Staff User',
            'phone_number' => '+44 7700 900444',
            'personal_email' => 'staff.personal@example.com',
            'job_title' => 'Engineer',
            'contract_type' => 'full_time',
            'start_date' => '2024-02-01',
            'work_email' => 'staff@oj.local',
        ])
        ->assertRedirect(route('directory.staff-information.edit'));
});

it('stores DBS certificates privately and only lets the owner or an admin download them', function () {
    Storage::fake('local');
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    $manager = User::query()->where('email', 'manager@oj.local')->firstOrFail();

    actingAs($staff)
        ->get(route('directory.staff-information.edit'))
        ->assertSee('DBS check certificate');

    actingAs($staff)
        ->post(route('directory.staff-information.update'), [
            'full_name' => 'Staff User',
            'phone_number' => '+44 7700 900123',
            'personal_email' => 'staff.personal@example.com',
            'job_title' => 'Developer',
            'contract_type' => 'full_time',
            'start_date' => '2024-01-15',
            'work_email' => 'staff@oj.local',
            'dbs' => UploadedFile::fake()->create('dbs.pdf', 80, 'application/pdf'),
        ])
        ->assertRedirect(route('directory.staff-information.edit'));

    $record = StaffInformation::query()->where('user_id', $staff->id)->firstOrFail();

    expect($record->dbs_original_name)->toBe('dbs.pdf')
        ->and(Storage::disk('local')->exists($record->dbs_path))->toBeTrue()
        ->and(Storage::disk('public')->exists($record->dbs_path))->toBeFalse();

    actingAs($staff)->get(route('directory.staff-information.dbs'))->assertOk();
    actingAs($admin)->get(route('admin.users.staff-information.dbs', $staff))->assertOk();

    actingAs($manager)->get(route('admin.users.staff-information.dbs', $staff))->assertForbidden();
});
