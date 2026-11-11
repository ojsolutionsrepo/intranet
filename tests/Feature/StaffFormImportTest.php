<?php

use App\Models\User;
use App\Modules\Directory\Livewire\StaffImport;
use App\Modules\Directory\Models\StaffInformation;
use App\Modules\Directory\Services\StaffImportService;
use Database\Seeders\DirectorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

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

it('falls back to personal email, keeps existing roles, and lets the latest response win', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();
    $adminRoles = $admin->getRoleNames()->all();

    $importer = app(StaffImportService::class);
    $preview = $importer->preview(formCsv([
        formRow([17 => 'admin@oj.local', 13 => 'Old title']),
        formRow([0 => '6/18/2026 14:26:42', 17 => 'admin@oj.local', 13 => 'New title']),
        formRow([1 => 'No Work Email', 4 => 'nowork@example.com', 17 => 'N/A']),
        formRow([1 => 'Bad Date', 17 => 'baddate@oj.local', 7 => 'sometime']),
    ]));

    expect(collect($preview['rows'])->where('_valid', false)->count())->toBe(1);
    $importer->commit($preview['rows']);

    $admin->refresh();
    expect($admin->getRoleNames()->all())->toBe($adminRoles)
        ->and($admin->staffInformation->job_title)->toBe('New title')
        ->and(User::query()->where('email', 'nowork@example.com')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'baddate@oj.local')->exists())->toBeFalse();
});

it('renders the import component and never sends bank details to the browser', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(StaffImport::class)
        ->assertOk()
        ->set('file', UploadedFile::fake()->createWithContent(
            'responses.csv',
            file_get_contents(formCsv([formRow()])->getRealPath()),
        ))
        ->call('preview')
        ->assertSet('isFormImport', true)
        ->assertSee('Test Person')
        ->assertDontSee('12345678')
        ->call('commit')
        ->assertSee('1 created');

    expect(User::query()->where('email', 'tess@oj.local')->exists())->toBeTrue();
});
