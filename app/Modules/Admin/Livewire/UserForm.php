<?php

namespace App\Modules\Admin\Livewire;

use App\Models\User;
use App\Shared\Models\Department;
use App\Shared\Models\UserProfile;
use App\Shared\Services\AuditLogger;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class UserForm extends Component
{
    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'Staff';

    public string $department_id = '';

    public string $job_title = '';

    public string $manager_id = '';

    public bool $is_active = true;

    public function mount(?int $userId = null): void
    {
        $this->userId = $userId;

        if (! $userId) {
            return;
        }

        $user = User::query()->with(['roles', 'departments'])->findOrFail($userId);
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->roles->first()?->name ?? 'Staff';
        $this->is_active = (bool) $user->is_active;
        $this->manager_id = $user->manager_id ? (string) $user->manager_id : '';
        $dept = $user->departments->first();
        $this->department_id = $dept ? (string) $dept->id : '';
        $this->job_title = (string) ($dept?->pivot?->job_title ?? '');
    }

    public function save(AuditLogger $audit): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($this->userId)],
            'password' => [$this->userId ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(['Admin', 'Manager', 'Staff', 'Guest'])],
            'department_id' => ['required', 'exists:departments,id'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['boolean'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $this->is_active,
            'manager_id' => $validated['manager_id'] !== '' ? (int) $validated['manager_id'] : null,
        ];

        if (! blank($this->password)) {
            $payload['password'] = $this->password;
        }

        if ($this->userId) {
            $user = User::query()->findOrFail($this->userId);
            $old = $user->only(['name', 'email', 'is_active', 'manager_id']);
            $user->update($payload);
            $action = 'user.updated';
        } else {
            $payload['email_verified_at'] = now();
            $payload['password'] = $this->password;
            $user = User::query()->create($payload);
            $old = null;
            $action = 'user.created';
            UserProfile::query()->create(['user_id' => $user->id]);
        }

        $user->syncRoles([$validated['role']]);
        $user->departments()->sync([
            (int) $validated['department_id'] => [
                'is_lead' => false,
                'job_title' => $validated['job_title'] ?: null,
            ],
        ]);

        $audit->log($action, $user, $old, $user->fresh()->only(['name', 'email', 'is_active', 'manager_id']));

        session()->flash('status', 'User saved.');
        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function render()
    {
        return view('admin-module::livewire.user-form', [
            'departments' => Department::query()->orderBy('name')->get(),
            'roles' => Role::query()->orderBy('name')->pluck('name'),
            'managers' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
