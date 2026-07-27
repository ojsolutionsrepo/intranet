<?php

namespace App\Modules\Directory\Services;

use App\Models\User;
use App\Shared\Models\Department;
use App\Shared\Models\Team;
use App\Shared\Models\UserProfile;
use App\Shared\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

final class StaffImportService
{
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
            $mapped = $this->mapRow($row);
            $rowErrors = $this->validateRow($mapped, $line);

            if ($rowErrors !== []) {
                foreach ($rowErrors as $message) {
                    $errors[] = ['row' => $line, 'message' => $message];
                }
                $mapped['_valid'] = false;
            } else {
                $mapped['_valid'] = true;
            }

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
     * @return array{created: int, updated: int, skipped: int}
     */
    public function commit(array $rows): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use ($rows, &$created, &$updated, &$skipped): void {
            foreach ($rows as $row) {
                if (! ($row['_valid'] ?? false)) {
                    $skipped++;

                    continue;
                }

                $existing = User::query()->where('email', $row['email'])->first();
                $user = User::query()->updateOrCreate(
                    ['email' => $row['email']],
                    [
                        'name' => $row['name'],
                        'password' => $existing?->password ?? Str::password(16),
                        'is_active' => true,
                        'email_verified_at' => $existing?->email_verified_at ?? now(),
                    ],
                );

                $role = Role::findOrCreate($row['role'] ?: 'Staff');
                $user->syncRoles([$role->name]);

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

                $expertise = $row['expertise']
                    ? array_values(array_filter(array_map('trim', explode(',', (string) $row['expertise']))))
                    : [];

                UserProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'phone' => $row['phone'] ?: null,
                        'extension' => $row['extension'] ?: null,
                        'location' => $row['location'] ?: null,
                        'expertise' => $expertise,
                        'start_date' => $row['start_date'] ?: null,
                    ],
                );

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
                $headers = array_map(fn ($h) => Str::slug((string) $h, '_'), $data);

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

        $headers = array_map(fn ($h) => Str::slug((string) $h, '_'), array_shift($sheet));
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
     * @param  array<string, string|null>  $row
     * @return array<string, string|null>
     */
    private function mapRow(array $row): array
    {
        $get = function (array $aliases) use ($row): ?string {
            foreach ($aliases as $alias) {
                if (array_key_exists($alias, $row) && $row[$alias] !== null && $row[$alias] !== '') {
                    return $row[$alias];
                }
            }

            return null;
        };

        return [
            'name' => $get(['name', 'full_name', 'staff_name']),
            'email' => strtolower((string) ($get(['email', 'email_address']) ?? '')),
            'department' => $get(['department', 'dept']),
            'team' => $get(['team', 'sub_team']),
            'job_title' => $get(['job_title', 'title', 'role_title']),
            'role' => $get(['role', 'access_role']) ?: 'Staff',
            'phone' => $get(['phone', 'mobile']),
            'extension' => $get(['extension', 'ext']),
            'location' => $get(['location', 'office']),
            'expertise' => $get(['expertise', 'skills']),
            'start_date' => $get(['start_date', 'started_at']),
        ];
    }

    /**
     * @param  array<string, string|null>  $row
     * @return list<string>
     */
    private function validateRow(array $row, int $line): array
    {
        $errors = [];

        if (blank($row['name'])) {
            $errors[] = "Row {$line}: name is required.";
        }
        if (blank($row['email']) || ! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Row {$line}: a valid email is required.";
        }
        if (blank($row['department'])) {
            $errors[] = "Row {$line}: department is required.";
        }
        if ($row['role'] && ! in_array($row['role'], ['Admin', 'Manager', 'Staff', 'Guest'], true)) {
            $errors[] = "Row {$line}: role must be Admin, Manager, Staff, or Guest.";
        }

        return $errors;
    }
}
