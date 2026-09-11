<?php

namespace App\Modules\Directory\Http\Requests;

use App\Models\User;
use App\Modules\Directory\Models\StaffInformation;
use App\Modules\Directory\Services\StaffInformationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        if (! $actor instanceof User) {
            return false;
        }

        $subject = $this->route('user');
        if (! $subject instanceof User) {
            $subject = $actor;
        }

        if ($subject->id !== $actor->id && ! StaffInformation::actorCanManage($actor)) {
            return false;
        }

        $record = app(StaffInformationService::class)->forUser($subject);

        return $record->actorCanEdit($actor);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:200'],
            'preferred_name' => ['nullable', 'string', 'max:120'],
            'phone_number' => ['required', 'string', 'max:40'],
            'personal_email' => ['required', 'email', 'max:255'],
            'gender' => ['nullable', Rule::in(array_keys(StaffInformation::GENDERS))],
            'nationality' => ['nullable', 'string', 'max:120'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'residential_address' => ['nullable', 'string', 'max:2000'],

            'nok_full_name' => ['nullable', 'string', 'max:200'],
            'nok_phone' => ['nullable', 'string', 'max:40'],
            'nok_address' => ['nullable', 'string', 'max:2000'],
            'nok_relationship' => ['nullable', 'string', 'max:120'],

            'job_title' => ['required', 'string', 'max:160'],
            'contract_type' => ['required', Rule::in(array_keys(StaffInformation::CONTRACT_TYPES))],
            'start_date' => ['required', 'date'],
            'role_responsibilities' => ['nullable', 'string', 'max:5000'],
            'work_email' => ['required', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:40'],
            'linkedin_url' => ['nullable', 'url', 'max:500'],

            'photo' => ['nullable', 'file', 'max:5120', 'extensions:jpg,jpeg,png,webp'],
            'cv' => ['nullable', 'file', 'max:10240', 'extensions:pdf,doc,docx'],

            'bank_name' => ['nullable', 'string', 'max:160'],
            'account_name' => ['nullable', 'string', 'max:200'],
            'account_number' => ['nullable', 'string', 'max:40'],
            'sort_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'full_name' => 'full name',
            'preferred_name' => 'preferred name',
            'phone_number' => 'phone number',
            'personal_email' => 'personal email address',
            'date_of_birth' => 'date of birth',
            'residential_address' => 'residential address',
            'nok_full_name' => 'next of kin full name',
            'nok_phone' => 'next of kin phone number',
            'nok_address' => 'next of kin address',
            'nok_relationship' => 'relationship to next of kin',
            'job_title' => 'job title',
            'contract_type' => 'contract type',
            'start_date' => 'start date',
            'role_responsibilities' => 'role and responsibilities',
            'work_email' => 'work email address',
            'company_phone' => 'company phone number',
            'linkedin_url' => 'LinkedIn profile URL',
            'photo' => 'employee photo',
            'cv' => 'CV / resume',
            'bank_name' => 'bank name',
            'account_name' => 'account name',
            'account_number' => 'account number',
            'sort_code' => 'sort code',
        ];
    }
}
