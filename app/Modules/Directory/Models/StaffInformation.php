<?php

namespace App\Modules\Directory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class StaffInformation extends Model
{
    protected $table = 'staff_information';

    public const CONTRACT_TYPES = [
        'full_time' => 'Full time',
        'part_time' => 'Part time',
        'contract' => 'Contract',
        'internship' => 'Internship',
    ];

    public const GENDERS = [
        'female' => 'Female',
        'male' => 'Male',
        'non_binary' => 'Non-binary',
        'prefer_not_to_say' => 'Prefer not to say',
        'other' => 'Other',
    ];

    protected $fillable = [
        'user_id',
        'full_name',
        'preferred_name',
        'phone_number',
        'personal_email',
        'gender',
        'nationality',
        'date_of_birth',
        'residential_address',
        'nok_full_name',
        'nok_phone',
        'nok_address',
        'nok_relationship',
        'job_title',
        'contract_type',
        'start_date',
        'role_responsibilities',
        'work_email',
        'company_phone',
        'linkedin_url',
        'photo_path',
        'photo_drive_url',
        'cv_path',
        'cv_original_name',
        'cv_drive_url',
        'dbs_check',
        'bank_name',
        'account_name',
        'account_number',
        'sort_code',
        'submitted_at',
        'staff_can_edit',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'start_date' => 'date',
            'submitted_at' => 'datetime',
            'account_number' => 'encrypted',
            'sort_code' => 'encrypted',
            'staff_can_edit' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? $this->publicDisk()->url($this->photo_path) : null;
    }

    public function cvUrl(): ?string
    {
        return $this->cv_path ? $this->publicDisk()->url($this->cv_path) : null;
    }

    private function publicDisk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk;
    }

    public function contractTypeLabel(): ?string
    {
        if ($this->contract_type === null) {
            return null;
        }

        return self::CONTRACT_TYPES[$this->contract_type] ?? $this->contract_type;
    }

    public function isComplete(): bool
    {
        return filled($this->full_name)
            && filled($this->phone_number)
            && filled($this->personal_email)
            && filled($this->job_title)
            && filled($this->contract_type)
            && filled($this->start_date)
            && filled($this->work_email)
            && filled($this->submitted_at);
    }

    public static function actorCanManage(User $actor): bool
    {
        return $actor->can('admin.users.manage') || $actor->can('directory.manage');
    }

    public function actorCanEdit(User $actor): bool
    {
        if (self::actorCanManage($actor)) {
            return true;
        }

        if ($actor->id !== $this->user_id) {
            return false;
        }

        // New / unsaved records default to editable for the owner.
        if (! $this->exists) {
            return true;
        }

        return (bool) $this->staff_can_edit;
    }
}
