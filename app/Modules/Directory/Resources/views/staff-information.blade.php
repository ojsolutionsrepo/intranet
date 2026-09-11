@extends('layouts.app')

@section('title', $adminMode ? 'Staff information — '.$subject->name : 'Staff information')

@section('breadcrumb')
    @if ($adminMode)
        <a href="{{ route('admin.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Administration</a>
        <span class="mx-2 text-[var(--ink-400)]">/</span>
        <a href="{{ route('admin.users.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Users</a>
        <span class="mx-2 text-[var(--ink-400)]">/</span>
        <a href="{{ route('admin.users.edit', $subject) }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">{{ $subject->name }}</a>
        <span class="mx-2 text-[var(--ink-400)]">/</span>
        <span class="text-[var(--ink-900)] font-medium">Staff information</span>
    @else
        <a href="{{ route('directory.index') }}" class="text-[var(--ink-500)] hover:text-[var(--ink-900)]">Directory</a>
        <span class="mx-2 text-[var(--ink-400)]">/</span>
        <span class="text-[var(--ink-900)] font-medium">Staff information</span>
    @endif
@endsection

@section('content')
    <div class="page-form-wide">
        <h1 class="font-display font-bold text-3xl tracking-tight mb-2">
            Staff information
            @if ($adminMode)
                <span class="note text-lg font-normal">— {{ $subject->name }}</span>
            @endif
        </h1>
        <p class="note mb-6">Personal, emergency, role, and bank details. Account number and sort code are stored encrypted.</p>

        @if (session('status'))
            <p class="badge badge-ok mb-4">{{ session('status') }}</p>
        @endif

        @if (! $canEdit)
            <p class="badge badge-warn mb-4">Editing is locked. An administrator must enable editing before you can make changes.</p>
        @endif

        @if ($adminMode && \App\Modules\Directory\Models\StaffInformation::actorCanManage($actor))
            <div class="card p-5 mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-display font-semibold text-base">Staff editing access</h2>
                    <p class="note text-xs mt-1">
                        @if ($record->exists ? $record->staff_can_edit : true)
                            Staff can currently edit this form.
                        @else
                            Staff editing is locked — only admins can change this record.
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.users.staff-information.editing', $subject) }}">
                        @csrf
                        <input type="hidden" name="staff_can_edit" value="1">
                        <button type="submit" class="btn btn-secondary btn-sm" @disabled($record->exists && $record->staff_can_edit)>Enable for staff</button>
                    </form>
                    <form method="POST" action="{{ route('admin.users.staff-information.editing', $subject) }}">
                        @csrf
                        <input type="hidden" name="staff_can_edit" value="0">
                        <button type="submit" class="btn btn-ghost btn-sm" @disabled($record->exists && ! $record->staff_can_edit)>Lock for staff</button>
                    </form>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <fieldset class="space-y-6 border-0 p-0 m-0 min-w-0" @disabled(! $canEdit)>
            <section class="card p-5 space-y-4">
                <h2 class="font-display font-semibold text-lg">Personal details</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="full_name">Full name</label>
                        <input id="full_name" type="text" name="full_name" value="{{ old('full_name', $record->full_name) }}" class="input" required maxlength="200">
                        @error('full_name') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="preferred_name">Preferred name</label>
                        <input id="preferred_name" type="text" name="preferred_name" value="{{ old('preferred_name', $record->preferred_name) }}" class="input" maxlength="120">
                        @error('preferred_name') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="phone_number">Phone number</label>
                        <input id="phone_number" type="text" name="phone_number" value="{{ old('phone_number', $record->phone_number) }}" class="input" required maxlength="40">
                        @error('phone_number') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="personal_email">Personal email address</label>
                        <input id="personal_email" type="email" name="personal_email" value="{{ old('personal_email', $record->personal_email) }}" class="input" required maxlength="255">
                        @error('personal_email') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="gender">Gender</label>
                        <select id="gender" name="gender" class="select">
                            <option value="">Select…</option>
                            @foreach ($genders as $value => $label)
                                <option value="{{ $value }}" @selected(old('gender', $record->gender) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('gender') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="nationality">Nationality</label>
                        <input id="nationality" type="text" name="nationality" value="{{ old('nationality', $record->nationality) }}" class="input" maxlength="120">
                        @error('nationality') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="date_of_birth">Date of birth</label>
                        <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($record->date_of_birth)->format('Y-m-d')) }}" class="input">
                        @error('date_of_birth') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="residential_address">Residential address</label>
                    <textarea id="residential_address" name="residential_address" class="input min-h-[5rem]" rows="3" maxlength="2000">{{ old('residential_address', $record->residential_address) }}</textarea>
                    @error('residential_address') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </section>

            <section class="card p-5 space-y-4">
                <h2 class="font-display font-semibold text-lg">Next of kin</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="nok_full_name">Full name</label>
                        <input id="nok_full_name" type="text" name="nok_full_name" value="{{ old('nok_full_name', $record->nok_full_name) }}" class="input" maxlength="200">
                        @error('nok_full_name') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="nok_phone">Phone number</label>
                        <input id="nok_phone" type="text" name="nok_phone" value="{{ old('nok_phone', $record->nok_phone) }}" class="input" maxlength="40">
                        @error('nok_phone') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="nok_address">Address</label>
                        <textarea id="nok_address" name="nok_address" class="input min-h-[4rem]" rows="2" maxlength="2000">{{ old('nok_address', $record->nok_address) }}</textarea>
                        @error('nok_address') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="nok_relationship">Relationship to next of kin</label>
                        <input id="nok_relationship" type="text" name="nok_relationship" value="{{ old('nok_relationship', $record->nok_relationship) }}" class="input" maxlength="120" placeholder="e.g. Spouse, Parent">
                        @error('nok_relationship') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="card p-5 space-y-4">
                <h2 class="font-display font-semibold text-lg">Role</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="job_title">Job title</label>
                        <input id="job_title" type="text" name="job_title" value="{{ old('job_title', $record->job_title) }}" class="input" required maxlength="160">
                        @error('job_title') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="contract_type">Contract type</label>
                        <select id="contract_type" name="contract_type" class="select" required>
                            <option value="">Select…</option>
                            @foreach ($contractTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('contract_type', $record->contract_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('contract_type') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="start_date">Start date</label>
                        <input id="start_date" type="date" name="start_date" value="{{ old('start_date', optional($record->start_date)->format('Y-m-d')) }}" class="input" required>
                        @error('start_date') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="work_email">Work email address</label>
                        <input id="work_email" type="email" name="work_email" value="{{ old('work_email', $record->work_email) }}" class="input" required maxlength="255">
                        @error('work_email') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="company_phone">Company phone number</label>
                        <input id="company_phone" type="text" name="company_phone" value="{{ old('company_phone', $record->company_phone) }}" class="input" maxlength="40">
                        <p class="note text-xs mt-1">Optional</p>
                        @error('company_phone') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="linkedin_url">LinkedIn profile URL</label>
                        <input id="linkedin_url" type="url" name="linkedin_url" value="{{ old('linkedin_url', $record->linkedin_url) }}" class="input" maxlength="500" placeholder="https://www.linkedin.com/in/…">
                        @error('linkedin_url') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="role_responsibilities">Describe your role and responsibilities</label>
                    <textarea id="role_responsibilities" name="role_responsibilities" class="input min-h-[8rem]" rows="5" maxlength="5000">{{ old('role_responsibilities', $record->role_responsibilities) }}</textarea>
                    @error('role_responsibilities') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </section>

            <section class="card p-5 space-y-4">
                <h2 class="font-display font-semibold text-lg">Photo &amp; CV</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="photo">Employee photo</label>
                        <input id="photo" type="file" name="photo" class="input" accept=".jpg,.jpeg,.png,.webp,image/*">
                        <p class="note text-xs mt-1">Clear headshot · JPG/PNG/WebP · max 5&nbsp;MB</p>
                        @if ($record->photoUrl())
                            <div class="mt-3 flex items-center gap-3">
                                <img src="{{ $record->photoUrl() }}" alt="Current photo" class="h-16 w-16 rounded-full object-cover border border-[var(--line)]">
                                <span class="note text-xs">Current photo on file</span>
                            </div>
                        @endif
                        @error('photo') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="cv">CV / Resume</label>
                        <input id="cv" type="file" name="cv" class="input" accept=".pdf,.doc,.docx">
                        <p class="note text-xs mt-1">PDF or Word · max 10&nbsp;MB</p>
                        @if ($record->cv_path)
                            <p class="mt-2 text-sm">
                                On file:
                                <a href="{{ $cvDownloadUrl }}" class="underline">{{ $record->cv_original_name ?: 'Download CV' }}</a>
                            </p>
                        @endif
                        @error('cv') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <section class="card p-5 space-y-4">
                <h2 class="font-display font-semibold text-lg">Bank details</h2>
                <p class="note text-xs">Used for payroll. Account number and UK sort code are encrypted at rest.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="bank_name">Bank name</label>
                        <input id="bank_name" type="text" name="bank_name" value="{{ old('bank_name', $record->bank_name) }}" class="input" maxlength="160">
                        @error('bank_name') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="account_name">Account name</label>
                        <input id="account_name" type="text" name="account_name" value="{{ old('account_name', $record->account_name) }}" class="input" maxlength="200">
                        @error('account_name') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="account_number">Account number</label>
                        <input id="account_number" type="text" name="account_number" value="{{ old('account_number', $record->account_number) }}" class="input" maxlength="40" autocomplete="off">
                        @error('account_number') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-[var(--ink-500)] mb-1" for="sort_code">Sort code</label>
                        <input id="sort_code" type="text" name="sort_code" value="{{ old('sort_code', $record->sort_code) }}" class="input" maxlength="20" placeholder="e.g. 12-34-56" autocomplete="off">
                        <p class="note text-xs mt-1">For staff paid in the UK</p>
                        @error('sort_code') <p class="text-[var(--err-600)] text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>
            </fieldset>

            @if ($canEdit)
                <button type="submit" class="btn btn-primary">Save staff information</button>
            @endif
        </form>
    </div>
@endsection
