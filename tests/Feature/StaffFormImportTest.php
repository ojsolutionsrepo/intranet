<?php

use App\Models\User;
use App\Modules\Directory\Models\StaffInformation;
use App\Modules\Directory\Services\StaffImportService;
use Database\Seeders\DirectorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(DirectorySeeder::class);
});

/** Headers exactly as the Employee Information Form responses sheet exports them. */
function formCsv(array $rows): UploadedFile
{
    $headers = [
        'Timestamp', 'Full Name', 'Preferred Name ', 'Phone Number ', 'Personal Email Address', 'Gender',
        'Nationality', 'Date of Birth', 'Residential Address ', 'Next of Kin - Full Name',
        'Next of Kin - Phone Number', 'Next of Kin - Address', 'Relationship to Next of Kin', 'Job Title',
        'Contract Type', 'Start Date', 'Describe Your Role and Responsibilities', 'Work Email Address ',
        'Company Phone Number', 'LinkedIn Profile URL', 'Employee Photo', '  Bank Name  ', '  Account Name  ',
        '  Account Number  ', "Sort Code\n(for those in the UK)", 'CV/Resume', 'DBS Check',
    ];

    $path = sys_get_temp_dir().'/form-import-'.uniqid().'.csv';
    $handle = fopen($path, 'w');
    fwrite($handle, "\xEF\xBB\xBF");
    fputcsv($handle, $headers);
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    return new UploadedFile($path, 'Employee Information Form (Responses).csv', 'text/csv', null, true);
}

function formRow(array $overrides = []): array
{
    return array_replace([
        '5/20/2026 13:09:48', 'Test Person,', 'Tess', '+440000000000', 'tess.personal@example.com', 'Female',
        'British', '10/12/1993', '1 Test Street', 'Kin Person', '07000000000', 'Same as above', 'Sister',
        'QA Lead', 'Full-time', '4/1/2026', 'Testing things.', 'tess@oj.local', 'N/A',
        'https://www.linkedin.com/in/test', 'https://drive.google.com/open?id=photo123', 'Test Bank',
        'Test Person', '12345678', '00-00-00', 'https://drive.google.com/open?id=cv123', '',
    ], $overrides);
}

it('imports Google Form responses into users and staff information', function () {
    $importer = app(StaffImportService::class);
    $preview = $importer->preview(formCsv([formRow()]));

    expect($preview['errors'])->toBe([]);
    $stats = $importer->commit($preview['rows']);
    expect($stats['created'])->toBe(1);

    $user = User::query()->where('email', 'tess@oj.local')->firstOrFail();
    $info = StaffInformation::query()->where('user_id', $user->id)->firstOrFail();

    expect($user->name)->toBe('Test Person')
        ->and($user->hasRole('Staff'))->toBeTrue()
        ->and($info->gender)->toBe('female')
        ->and($info->contract_type)->toBe('full_time')
        ->and($info->date_of_birth->toDateString())->toBe('1993-10-12')
        ->and($info->start_date->toDateString())->toBe('2026-04-01')
        ->and($info->submitted_at->toDateTimeString())->toBe('2026-05-20 13:09:48')
        ->and($info->nok_relationship)->toBe('Sister')
        ->and($info->company_phone)->toBeNull()
        ->and($info->account_number)->toBe('12345678')
        ->and($info->sort_code)->toBe('00-00-00')
        ->and($info->photo_drive_url)->toBe('https://drive.google.com/open?id=photo123')
        ->and($info->cv_drive_url)->toBe('https://drive.google.com/open?id=cv123');
});

it('requires a staff email, keeps existing roles, and lets the latest response win', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    $adminRoles = $admin->getRoleNames()->all();

    $importer = app(StaffImportService::class);
    $preview = $importer->preview(formCsv([
        formRow([17 => 'admin@oj.local', 13 => 'Old title']),
        formRow([0 => '6/18/2026 14:26:42', 17 => 'admin@oj.local', 13 => 'New title']),
        formRow([1 => 'No Work Email', 4 => 'nowork@example.com', 17 => 'N/A']),
        formRow([1 => 'Bad Date', 17 => 'baddate@oj.local', 7 => 'sometime']),
    ]));

    // No staff email ("N/A") and a bad date are both rejected; personal email is not a fallback.
    expect(collect($preview['rows'])->where('_valid', false)->count())->toBe(2)
        ->and(collect($preview['errors'])->pluck('message')->implode(' '))->toContain('staff (work) email');
    $importer->commit($preview['rows']);

    $admin->refresh();
    expect($admin->getRoleNames()->all())->toBe($adminRoles)
        ->and($admin->staffInformation->job_title)->toBe('New title')
        ->and(User::query()->where('email', 'nowork@example.com')->exists())->toBeFalse()
        ->and(User::query()->where('email', 'baddate@oj.local')->exists())->toBeFalse();
});

it('matches existing accounts by staff email only', function () {
    $existing = User::factory()->create(['name' => 'Victor Cobham', 'email' => 'victor@oj.local']);
    $sameName = User::factory()->create(['name' => 'Same Name', 'email' => 'same.name@oj.local']);
    $usersBefore = User::query()->count();

    $importer = app(StaffImportService::class);
    $preview = $importer->preview(formCsv([
        // Staff email matches the login email, even though the name differs.
        formRow([1 => 'Victor Cobham Nsa', 17 => 'VICTOR@oj.local']),
        // Same name but a different staff email: not matched by name.
        formRow([1 => 'Same Name', 17 => 'someone.new@oj.local', 4 => 'same.name@oj.local']),
    ]));

    expect($preview['rows'][0]['match_user_id'])->toBe($existing->id)
        ->and($preview['rows'][0]['match_reason'])->toBe('staff email')
        ->and($preview['rows'][1]['match_user_id'])->toBeNull();

    $stats = $importer->commit($preview['rows']);

    $existing->refresh();
    expect($stats)->toMatchArray(['created' => 1, 'updated' => 1])
        ->and(User::query()->count())->toBe($usersBefore + 1)
        ->and($existing->name)->toBe('Victor Cobham')
        ->and($existing->staffInformation->full_name)->toBe('Victor Cobham Nsa')
        ->and($sameName->fresh()->staffInformation)->toBeNull();

    // Re-importing finds people by the staff email stored on their staff information too.
    $existing->update(['email' => 'victor.login@oj.local']);
    $again = $importer->preview(formCsv([formRow([17 => 'victor@oj.local'])]));
    expect($again['rows'][0]['match_user_id'])->toBe($existing->id);
});

it('never gives one staff email to two people', function () {
    $owner = User::factory()->create(['email' => 'owner@oj.local']);
    $other = User::factory()->create(['email' => 'other@oj.local']);

    $importer = app(StaffImportService::class);
    $preview = $importer->preview(formCsv([formRow([17 => 'owner@oj.local'])]));
    $stats = $importer->commit($preview['rows'], [0 => $other->id]);

    expect($stats['skipped'])->toBe(1)
        ->and($other->fresh()->staffInformation)->toBeNull();

    // The staff information form enforces it as well.
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    actingAs($admin)
        ->post(route('admin.users.staff-information.update', $other), [
            'full_name' => 'Other', 'phone_number' => '1', 'personal_email' => 'p@example.com',
            'job_title' => 'X', 'contract_type' => 'full_time', 'start_date' => '2024-01-01',
            'work_email' => 'OWNER@oj.local',
        ])
        ->assertSessionHasErrors('work_email');
});

it('uploads, previews without leaking bank details, and commits through the import page', function () {
    Storage::fake('local');
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    $csv = file_get_contents(formCsv([formRow()])->getRealPath());

    // Background upload used by the progress bar.
    actingAs($admin)
        ->postJson(route('directory.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('responses.csv', $csv),
        ])
        ->assertOk()
        ->assertJson(['ok' => true, 'next' => route('directory.import')]);

    actingAs($admin)
        ->get(route('directory.import'))
        ->assertOk()
        ->assertSee('Employee Information Form detected')
        ->assertSee('Test Person')
        ->assertDontSee('12345678');

    actingAs($admin)
        ->post(route('directory.import.commit'))
        ->assertRedirect(route('directory.import'));

    expect(User::query()->where('email', 'tess@oj.local')->exists())->toBeTrue()
        ->and(Storage::disk('local')->allFiles('staff-imports'))->toBe([]);
});

it('lets the admin override matches in the preview', function () {
    Storage::fake('local');
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    $target = User::factory()->create(['name' => 'Someone Else', 'email' => 'someone@oj.local']);
    $csv = file_get_contents(formCsv([
        formRow([1 => 'Unmatched Person', 17 => 'unmatched@example.com']),
        formRow([1 => 'Skip Me', 17 => 'skipme@example.com']),
    ])->getRealPath());

    actingAs($admin)
        ->post(route('directory.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('responses.csv', $csv),
        ])
        ->assertRedirect(route('directory.import'));

    actingAs($admin)
        ->post(route('directory.import.commit'), ['mappings' => [0 => (string) $target->id, 1 => 'skip']])
        ->assertSessionHas('status', 'Import complete: 0 created, 1 updated, 1 skipped.');

    expect($target->fresh()->staffInformation->full_name)->toBe('Unmatched Person')
        ->and(User::query()->where('email', 'unmatched@example.com')->exists())->toBeFalse()
        ->and(User::query()->where('email', 'skipme@example.com')->exists())->toBeFalse();
});

it('refuses the import page for users without import permission', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    actingAs($staff)->get(route('directory.import'))->assertForbidden();
    actingAs($staff)
        ->post(route('directory.import.preview'), [
            'file' => UploadedFile::fake()->createWithContent('responses.csv', 'name,email'),
        ])
        ->assertForbidden();
});
