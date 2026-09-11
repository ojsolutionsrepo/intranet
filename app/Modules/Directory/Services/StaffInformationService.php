<?php

namespace App\Modules\Directory\Services;

use App\Models\User;
use App\Modules\Directory\Models\StaffInformation;
use App\Shared\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class StaffInformationService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ProfilePhotoService $photos,
    ) {}

    public function forUser(User $user): StaffInformation
    {
        $existing = StaffInformation::query()->where('user_id', $user->id)->first();
        if ($existing) {
            return $existing;
        }

        $profile = $user->profile;

        return new StaffInformation([
            'user_id' => $user->id,
            'full_name' => $user->name,
            'phone_number' => $profile?->phone,
            'work_email' => $user->email,
            'job_title' => $user->jobTitle(),
            'start_date' => $profile?->start_date,
            'company_phone' => $profile?->extension,
            'staff_can_edit' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(User $subject, User $actor, array $data, ?UploadedFile $photo = null, ?UploadedFile $cv = null): StaffInformation
    {
        $record = $this->forUser($subject);
        abort_unless($record->actorCanEdit($actor), 403, 'Staff information is locked for editing.');

        return DB::transaction(function () use ($subject, $actor, $data, $photo, $cv, $record) {
            $persisted = StaffInformation::query()->firstOrNew(['user_id' => $subject->id]);
            if (! $persisted->exists) {
                $persisted->staff_can_edit = true;
            }

            $before = $persisted->exists ? $persisted->only([
                'full_name', 'preferred_name', 'job_title', 'contract_type', 'work_email', 'submitted_at', 'staff_can_edit',
            ]) : null;

            $persisted->fill([
                'full_name' => $data['full_name'],
                'preferred_name' => $data['preferred_name'] ?? null,
                'phone_number' => $data['phone_number'],
                'personal_email' => $data['personal_email'],
                'gender' => $data['gender'] ?? null,
                'nationality' => $data['nationality'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'residential_address' => $data['residential_address'] ?? null,
                'nok_full_name' => $data['nok_full_name'] ?? null,
                'nok_phone' => $data['nok_phone'] ?? null,
                'nok_address' => $data['nok_address'] ?? null,
                'nok_relationship' => $data['nok_relationship'] ?? null,
                'job_title' => $data['job_title'],
                'contract_type' => $data['contract_type'],
                'start_date' => $data['start_date'],
                'role_responsibilities' => $data['role_responsibilities'] ?? null,
                'work_email' => $data['work_email'],
                'company_phone' => $data['company_phone'] ?? null,
                'linkedin_url' => $data['linkedin_url'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'account_name' => $data['account_name'] ?? null,
                'account_number' => $data['account_number'] ?? null,
                'sort_code' => $data['sort_code'] ?? null,
                'submitted_at' => now(),
            ]);

            if ($photo) {
                $persisted->photo_path = $this->storePhoto($subject, $photo, $persisted->photo_path);
                $this->photos->store($subject, $photo);
            }

            if ($cv) {
                $persisted->cv_path = $this->storeCv($subject, $cv, $persisted->cv_path);
                $persisted->cv_original_name = $cv->getClientOriginalName();
            }

            $persisted->save();

            $profile = $subject->profile()->firstOrCreate(['user_id' => $subject->id]);
            $profile->update([
                'phone' => $persisted->phone_number,
                'start_date' => $persisted->start_date,
            ]);

            if ($subject->name !== $persisted->full_name) {
                $subject->update(['name' => $persisted->full_name]);
            }

            $this->audit->log('staff_information.saved', $subject, $before, [
                'full_name' => $persisted->full_name,
                'job_title' => $persisted->job_title,
                'contract_type' => $persisted->contract_type,
                'has_photo' => filled($persisted->photo_path),
                'has_cv' => filled($persisted->cv_path),
                'edited_by' => $actor->id,
                'admin_edit' => $actor->id !== $subject->id,
            ]);

            return $persisted->fresh() ?? $record;
        });
    }

    public function setStaffCanEdit(User $subject, User $actor, bool $enabled): StaffInformation
    {
        abort_unless(StaffInformation::actorCanManage($actor), 403);

        $record = StaffInformation::query()->firstOrNew(['user_id' => $subject->id]);
        if (! $record->exists) {
            $record->full_name = $subject->name;
            $record->work_email = $subject->email;
        }

        $before = ['staff_can_edit' => $record->exists ? (bool) $record->staff_can_edit : true];
        $record->staff_can_edit = $enabled;
        $record->save();

        $this->audit->log(
            $enabled ? 'staff_information.editing_enabled' : 'staff_information.editing_locked',
            $subject,
            $before,
            ['staff_can_edit' => $enabled, 'by' => $actor->id],
        );

        return $record->fresh() ?? $record;
    }

    private function storePhoto(User $user, UploadedFile $file, ?string $previous): string
    {
        $dir = 'staff-information/'.$user->id;
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $path = $file->storeAs($dir, 'photo-'.Str::uuid().'.'.$ext, 'public');

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Could not store employee photo.');
        }

        if ($previous && $previous !== $path) {
            Storage::disk('public')->delete($previous);
        }

        return $path;
    }

    private function storeCv(User $user, UploadedFile $file, ?string $previous): string
    {
        $dir = 'staff-information/'.$user->id;
        $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $path = $file->storeAs($dir, 'cv-'.Str::uuid().'.'.$ext, 'public');

        if (! is_string($path) || $path === '') {
            throw new \RuntimeException('Could not store CV.');
        }

        if ($previous && $previous !== $path) {
            Storage::disk('public')->delete($previous);
        }

        return $path;
    }
}
