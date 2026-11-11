<?php

namespace App\Modules\Directory\Services;

use App\Models\User;
use App\Modules\Directory\Models\StaffInformation;
use App\Shared\Models\Department;
use App\Shared\Models\Team;
use App\Shared\Models\UserProfile;
use App\Shared\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Imports staff from either:
 *  - the simple directory template (name, email, department, …), or
 *  - the "Employee Information Form" Google Form responses sheet
 *    (downloaded as CSV/XLSX), which also fills staff_information.
 */
final class StaffImportService
{
    /** Cell values that people type to mean "nothing". */
    private const EMPTY_VALUES = ['n/a', 'na', 'none', 'nil', '-', '--', 'null'];

    /** Header slugs that only appear in the Google Form export. */
    private const FORM_MARKERS = ['personal_email_address', 'next_of_kin_full_name', 'work_email_address'];

    /** Columns shown in the preview table (kept free of personal data). */
    public const PREVIEW_COLUMNS = ['name', 'email', 'department', 'job_title', 'role', 'source', '_valid', 'match_user_id', 'match_reason'];

    /**
     * @return array{headers: list<string>, rows: list<array<string, mixed>>, errors: list<array{row: int, message: string}>}
     */
    public function preview(UploadedFile $file): array
    {
        $rows = $this->parseFile($file);
        $errors = [];
        $normalized = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2; // header is line 1
            $rowErrors = [];
            $mapped = $this->mapRow($row, $rowErrors, $line);
            $rowErrors = [...$rowErrors, ...$this->validateRow($mapped, $line)];

            foreach ($rowErrors as $message) {
                $errors[] = ['row' => $line, 'message' => $message];
            }
            $mapped['_valid'] = $rowErrors === [];
            [$mapped['match_user_id'], $mapped['match_reason']] = $this->matchExistingUser($mapped);

            $normalized[] = $mapped;
        }

        return [
            'headers' => ['name', 'email', 'department', 'team', 'job_title', 'role', 'phone', 'extension', 'location', 'expertise', 'start_date'],
            'rows' => $normalized,
            'errors' => $errors,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<int, int|string>  $mappings  row index => existing user id, 'new' or 'skip'.
     *                                            Rows without an entry use the automatic match.
     * @return array{created: int, updated: int, skipped: int}
     */
    public function commit(array $rows, array $mappings = []): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, $mappings, &$created, &$updated, &$skipped): void {
            foreach ($rows as $index => $row) {
                $target = $mappings[$index] ?? ($row['match_user_id'] ?? null) ?? 'new';

                if (! ($row['_valid'] ?? false) || $target === 'skip') {
                    $skipped++;

                    continue;
                }

                $existing = is_numeric($target)
                    ? User::query()->find((int) $target)
                    : $this->findUser($row); // 'new', but never duplicate an email that already exists

                if ($existing) {
                    // The existing account is authoritative: keep its login email and display name.
                    $user = $existing;
                } else {
                    $user = User::query()->create([
                        'email' => $row['email'],
                        'name' => $row['name'],
                        'password' => Str::password(16),
                        'is_active' => true,
                        'email_verified_at' => now(),
                    ]);
                }

                // Never demote an existing user just because the file has no role column.
                if ($row['role']) {
                    $user->syncRoles([Role::findOrCreate($row['role'])->name]);
                } elseif (! $existing) {
                    $user->syncRoles([Role::findOrCreate('Staff')->name]);
                }

                if ($row['department']) {
                    $department = Department::query()->firstOrCreate(
                        ['slug' => Str::slug($row['department'])],
                        ['name' => $row['department'], 'order' => 0],
                    );

                    $user->departments()->sync([
                        $department->id => [
                            'is_lead' => false,
                            'job_title' => $row['job_title'] ?: null,
                        ],
                    ]);

                    if ($row['team']) {
                        $team = Team::query()->firstOrCreate(
                            [
                                'department_id' => $department->id,
                                'slug' => Str::slug($row['team']),
                            ],
                            ['name' => $row['team']],
                        );
                        $user->teams()->sync([$team->id]);
                    }
                }

                $expertise = $row['expertise']
                    ? array_values(array_filter(array_map('trim', explode(',', (string) $row['expertise']))))
                    : null;

                // Only overwrite profile fields the file actually supplies.
                $profile = array_filter([
                    'phone' => $row['phone'],
                    'extension' => $row['extension'],
                    'location' => $row['location'],
                    'expertise' => $expertise,
                    'start_date' => $row['start_date'],
                ], fn ($v) => $v !== null && $v !== '');

                UserProfile::query()->updateOrCreate(['user_id' => $user->id], $profile);

                if ($row['source'] === 'form') {
                    $this->saveStaffInformation($user, $row);
                }

                if ($existing) {
                    $updated++;
                } else {
                    $created++;
                }
            }
        });

        app(AuditLogger::class)->log('staff.import', null, null, [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ]);

        return compact('created', 'updated', 'skipped');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function findUser(array $row): ?User
    {
        foreach (array_filter([$row['email'], $row['personal_email'] ?? null]) as $email) {
            $user = User::query()->whereRaw('LOWER(email) = ?', [strtolower((string) $email)])->first();
            if ($user) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Best guess at which existing account a row belongs to.
     * Order: login email → staff information emails → unambiguous name match.
     *
     * @param  array<string, mixed>  $row
     * @return array{0: int|null, 1: string|null}
     */
    public function matchExistingUser(array $row): array
    {
        if ($user = $this->findUser($row)) {
            return [$user->id, 'email'];
        }

        $emails = array_values(array_filter([$row['email'] ?? null, $row['work_email'] ?? null, $row['personal_email'] ?? null]));
        if ($emails !== []) {
            $userId = StaffInformation::query()
                ->where(function ($q) use ($emails) {
                    foreach ($emails as $email) {
                        $q->orWhereRaw('LOWER(work_email) = ?', [strtolower($email)])
                            ->orWhereRaw('LOWER(personal_email) = ?', [strtolower($email)]);
                    }
                })
                ->value('user_id');
            if ($userId) {
                return [(int) $userId, 'email'];
            }
        }

        $tokens = $this->nameTokens((string) ($row['name'] ?? ''));
        if (count($tokens) < 2) {
            return [null, null];
        }

        $candidates = User::query()
            ->with('staffInformation:id,user_id,full_name')
            ->get(['id', 'name'])
            ->filter(function (User $user) use ($tokens) {
                foreach (array_filter([$user->name, $user->staffInformation?->full_name]) as $name) {
                    $theirs = $this->nameTokens($name);
                    if (count($theirs) < 2) {
                        continue;
                    }
                    // e.g. "Victor Cobham" matches "Victor Cobham Nsa", in any word order.
                    $shorter = count($theirs) <= count($tokens) ? $theirs : $tokens;
                    $longer = $shorter === $theirs ? $tokens : $theirs;
                    if (array_diff($shorter, $longer) === []) {
                        return true;
                    }
                }

                return false;
            });

        return $candidates->count() === 1 ? [$candidates->first()->id, 'name'] : [null, null];
    }

    /**
     * @return list<string>
     */
    private function nameTokens(string $name): array
    {
        $clean = Str::lower(Str::ascii($name));
        $parts = preg_split('/[^a-z]+/', $clean, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter($parts, fn ($p) => strlen($p) > 1)));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function saveStaffInformation(User $user, array $row): void
    {
        $record = StaffInformation::query()->firstOrNew(['user_id' => $user->id]);

        // Don't let an older form response overwrite details edited on the intranet since.
        if ($record->exists && $record->submitted_at && $row['submitted_at']
            && $record->submitted_at->greaterThan(CarbonImmutable::parse($row['submitted_at']))) {
            return;
        }

        if (! $record->exists) {
            $record->staff_can_edit = true;
        }

        $record->fill([
            'full_name' => $row['name'],
            'preferred_name' => $row['preferred_name'],
            'phone_number' => $row['phone'],
            'personal_email' => $row['personal_email'],
            'gender' => $row['gender'],
            'nationality' => $row['nationality'],
            'date_of_birth' => $row['date_of_birth'],
            'residential_address' => $row['residential_address'],
            'nok_full_name' => $row['nok_full_name'],
            'nok_phone' => $row['nok_phone'],
            'nok_address' => $row['nok_address'],
            'nok_relationship' => $row['nok_relationship'],
            'job_title' => $row['job_title'],
            'contract_type' => $row['contract_type'],
            'start_date' => $row['start_date'],
            'role_responsibilities' => $row['role_responsibilities'],
            'work_email' => $row['work_email'],
            'company_phone' => $row['company_phone'],
            'linkedin_url' => $row['linkedin_url'],
            'photo_drive_url' => $row['photo_drive_url'],
            'cv_drive_url' => $row['cv_drive_url'],
            'dbs_check' => $row['dbs_check'],
            'bank_name' => $row['bank_name'],
            'account_name' => $row['account_name'],
            'account_number' => $row['account_number'],
            'sort_code' => $row['sort_code'],
            'submitted_at' => $row['submitted_at'] ?? now(),
        ]);
        $record->save();
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function parseFile(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if (in_array($ext, ['xlsx', 'xls'], true)) {
            return $this->parseSpreadsheet($file);
        }

        return $this->parseCsv($file);
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw new \RuntimeException('Could not read CSV file.');
        }

        $headers = null;
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($headers === null) {
                $headers = $this->normalizeHeaders($data);

                continue;
            }

            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = isset($data[$i]) ? trim((string) $data[$i]) : null;
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function parseSpreadsheet(UploadedFile $file): array
    {
        if (! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \RuntimeException('XLSX import requires phpoffice/phpspreadsheet. Upload CSV instead.');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet()->toArray();
        if ($sheet === []) {
            return [];
        }

        $headers = $this->normalizeHeaders(array_shift($sheet));
        $rows = [];

        foreach ($sheet as $data) {
            if (count(array_filter($data, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = isset($data[$i]) ? trim((string) $data[$i]) : null;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  array<int, mixed>  $headers
     * @return list<string>
     */
    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($h) {
            // Strip a UTF-8 BOM (Excel/Sheets CSV exports) before slugging.
            $h = preg_replace('/^\xEF\xBB\xBF/', '', (string) $h);

            return Str::slug(str_replace('/', ' ', (string) $h), '_');
        }, $headers);
    }

    /**
     * @param  array<string, string|null>  $row
     * @param  list<string>  $errors
     * @return array<string, mixed>
     */
    private function mapRow(array $row, array &$errors, int $line): array
    {
        $get = function (array $aliases) use ($row): ?string {
            foreach ($aliases as $alias) {
                $value = $row[$alias] ?? null;
                if ($value === null) {
                    continue;
                }
                $value = trim($value);
                if ($value === '' || in_array(strtolower($value), self::EMPTY_VALUES, true)) {
                    continue;
                }

                return $value;
            }

            return null;
        };

        $date = function (array $aliases, string $label) use ($get, &$errors, $line): ?string {
            $raw = $get($aliases);
            if ($raw === null) {
                return null;
            }
            $parsed = $this->parseDate($raw);
            if ($parsed === null) {
                $errors[] = "Row {$line}: {$label} \"{$raw}\" is not a recognised date.";
            }

            return $parsed?->toDateString();
        };

        $isForm = array_intersect(self::FORM_MARKERS, array_keys($row)) !== [];

        $personalEmail = $get(['personal_email_address', 'personal_email']);
        $workEmail = $get(['email', 'email_address', 'work_email_address', 'work_email']);
        $loginEmail = $workEmail;
        if ($isForm && ($loginEmail === null || ! filter_var($loginEmail, FILTER_VALIDATE_EMAIL))) {
            $loginEmail = $personalEmail; // form allows "N/A" for work email
        }

        $submitted = $get(['timestamp']);

        $mapped = [
            'source' => $isForm ? 'form' : 'directory',
            'name' => ($n = $get(['name', 'full_name', 'staff_name'])) !== null ? rtrim($n, " ,") : null,
            'email' => strtolower((string) ($loginEmail ?? '')),
            'department' => $get(['department', 'dept']),
            'team' => $get(['team', 'sub_team']),
            'job_title' => $get(['job_title', 'title', 'role_title']),
            'role' => $get(['role', 'access_role']),
            'phone' => $get(['phone', 'phone_number', 'mobile']),
            'extension' => $get(['extension', 'ext']),
            'location' => $get(['location', 'office']),
            'expertise' => $get(['expertise', 'skills']),
            'start_date' => $date(['start_date', 'started_at'], 'start date'),
        ];

        if (! $isForm) {
            return $mapped;
        }

        return $mapped + [
            'submitted_at' => $submitted !== null ? $this->parseDate($submitted)?->toDateTimeString() : null,
            'preferred_name' => $get(['preferred_name']),
            'personal_email' => $personalEmail !== null ? strtolower($personalEmail) : null,
            'gender' => $this->normalizeGender($get(['gender'])),
            'nationality' => $get(['nationality']),
            'date_of_birth' => $date(['date_of_birth', 'dob'], 'date of birth'),
            'residential_address' => $get(['residential_address', 'address']),
            'nok_full_name' => $get(['next_of_kin_full_name']),
            'nok_phone' => $get(['next_of_kin_phone_number']),
            'nok_address' => $get(['next_of_kin_address']),
            'nok_relationship' => $get(['relationship_to_next_of_kin']),
            'contract_type' => $this->normalizeContractType($get(['contract_type'])),
            'role_responsibilities' => $get(['describe_your_role_and_responsibilities', 'role_and_responsibilities']),
            'work_email' => $workEmail !== null && filter_var($workEmail, FILTER_VALIDATE_EMAIL) ? strtolower($workEmail) : null,
            'company_phone' => $get(['company_phone_number', 'company_phone']),
            'linkedin_url' => $get(['linkedin_profile_url', 'linkedin_url', 'linkedin']),
            'photo_drive_url' => $this->safeUrl($get(['employee_photo', 'photo'])),
            'cv_drive_url' => $this->safeUrl($get(['cv_resume', 'cv'])),
            'dbs_check' => $get(['dbs_check', 'dbs']),
            'bank_name' => $get(['bank_name']),
            'account_name' => $get(['account_name']),
            'account_number' => $get(['account_number']),
            'sort_code' => $get(['sort_code_for_those_in_the_uk', 'sort_code']),
        ];
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        if (is_numeric($value) && class_exists(\PhpOffice\PhpSpreadsheet\Shared\Date::class)) {
            return CarbonImmutable::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value));
        }

        // ISO first, then Google Forms' default US format (month/day/year).
        $formats = ['Y-m-d', 'Y-m-d H:i:s', 'n/j/Y', 'n/j/Y G:i:s', 'n/j/Y G:i', 'm/d/Y', 'm/d/Y H:i:s'];
        foreach ($formats as $format) {
            try {
                $parsed = CarbonImmutable::createFromFormat('!'.$format, $value);
            } catch (\Throwable) {
                continue;
            }
            if ($parsed && $parsed->format($format) === $value) {
                return $parsed;
            }
        }

        return null;
    }

    private function normalizeGender(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $key = Str::slug($value, '_');

        return array_key_exists($key, StaffInformation::GENDERS) ? $key : $value;
    }

    private function normalizeContractType(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $key = Str::slug($value, '_');
        $key = match ($key) {
            'fulltime', 'full' => 'full_time',
            'parttime', 'part' => 'part_time',
            'intern' => 'internship',
            default => $key,
        };

        return array_key_exists($key, StaffInformation::CONTRACT_TYPES) ? $key : $value;
    }

    private function safeUrl(?string $value): ?string
    {
        return $value !== null && preg_match('#^https?://#i', $value) ? $value : null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function validateRow(array $row, int $line): array
    {
        $errors = [];

        if (blank($row['name'])) {
            $errors[] = "Row {$line}: name is required.";
        }
        if (blank($row['email']) || ! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = $row['source'] === 'form'
                ? "Row {$line}: needs a valid work or personal email."
                : "Row {$line}: a valid email is required.";
        }
        if ($row['source'] !== 'form' && blank($row['department'])) {
            $errors[] = "Row {$line}: department is required.";
        }
        if ($row['role'] && ! in_array($row['role'], ['Admin', 'Manager', 'Staff', 'Guest'], true)) {
            $errors[] = "Row {$line}: role must be Admin, Manager, Staff, or Guest.";
        }

        return $errors;
    }
}
