<?php

namespace App\Modules\Admin\Livewire;

use App\Models\User;
use App\Modules\Admin\Services\DepartmentAdminService;
use App\Shared\Models\Department;
use Livewire\Component;

class DepartmentManager extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $parent_id = '';

    public string $lead_user_id = '';

    public int $order = 0;

    public function edit(int $id): void
    {
        $department = Department::query()->findOrFail($id);
        $this->editingId = $department->id;
        $this->name = $department->name;
        $this->description = (string) ($department->description ?? '');
        $this->parent_id = $department->parent_id ? (string) $department->parent_id : '';
        $this->lead_user_id = $department->lead_user_id ? (string) $department->lead_user_id : '';
        $this->order = (int) $department->order;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function save(DepartmentAdminService $admin): void
    {
        $this->parent_id = $this->parent_id === '' ? '' : $this->parent_id;
        $this->lead_user_id = $this->lead_user_id === '' ? '' : $this->lead_user_id;

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'string'],
            'lead_user_id' => ['nullable', 'string'],
            'order' => ['integer', 'min:0', 'max:9999'],
        ]);

        $parentId = $data['parent_id'] !== '' ? (int) $data['parent_id'] : null;
        $leadId = $data['lead_user_id'] !== '' ? (int) $data['lead_user_id'] : null;

        if ($parentId && ! Department::query()->whereKey($parentId)->exists()) {
            $this->addError('parent_id', 'Selected parent department was not found.');

            return;
        }

        if ($leadId && ! User::query()->whereKey($leadId)->exists()) {
            $this->addError('lead_user_id', 'Selected lead was not found.');

            return;
        }

        $payload = [
            'name' => $data['name'],
            'description' => ($data['description'] ?? '') !== '' ? $data['description'] : null,
            'parent_id' => $parentId,
            'lead_user_id' => $leadId,
            'order' => (int) $data['order'],
        ];

        if ($this->editingId) {
            $department = Department::query()->findOrFail($this->editingId);
            $admin->update($department, $payload);
            session()->flash('status', "Updated {$department->fresh()->name}.");
        } else {
            $department = $admin->create($payload);
            session()->flash('status', "Created {$department->name}.");
        }

        $this->resetForm();
    }

    public function delete(int $id, DepartmentAdminService $admin): void
    {
        $department = Department::query()->findOrFail($id);
        $admin->delete($department);
        if ($this->editingId === $id) {
            $this->resetForm();
        }
        session()->flash('status', "Removed {$department->name}.");
    }

    public function render()
    {
        return view('admin-module::livewire.departments', [
            'departments' => Department::query()
                ->with(['parent', 'lead'])
                ->withCount('users')
                ->orderBy('order')
                ->orderBy('name')
                ->get(),
            'parents' => Department::query()
                ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
                ->orderBy('name')
                ->get(['id', 'name']),
            'leads' => User::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->parent_id = '';
        $this->lead_user_id = '';
        $this->order = 0;
        $this->resetValidation();
    }
}
