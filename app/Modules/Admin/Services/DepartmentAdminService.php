<?php

namespace App\Modules\Admin\Services;

use App\Shared\Models\Department;
use App\Shared\Services\AuditLogger;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class DepartmentAdminService
{
    /**
     * @param  array{name: string, description?: string|null, parent_id?: int|null, lead_user_id?: int|null, order?: int}  $data
     */
    public function create(array $data): Department
    {
        $department = Department::query()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'parent_id' => $data['parent_id'] ?: null,
            'lead_user_id' => $data['lead_user_id'] ?: null,
            'order' => $data['order'] ?? 0,
        ]);

        app(AuditLogger::class)->log('department.created', $department, null, $department->only([
            'name', 'slug', 'parent_id', 'lead_user_id', 'order',
        ]));

        return $department;
    }

    /**
     * @param  array{name: string, description?: string|null, parent_id?: int|null, lead_user_id?: int|null, order?: int}  $data
     */
    public function update(Department $department, array $data): Department
    {
        $before = $department->only(['name', 'slug', 'description', 'parent_id', 'lead_user_id', 'order']);

        if (($data['parent_id'] ?? null) && (int) $data['parent_id'] === $department->id) {
            throw ValidationException::withMessages([
                'parent_id' => 'A department cannot be its own parent.',
            ]);
        }

        $slug = $department->name !== $data['name']
            ? $this->uniqueSlug($data['name'], $department->id)
            : $department->slug;

        $department->update([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'parent_id' => $data['parent_id'] ?: null,
            'lead_user_id' => $data['lead_user_id'] ?: null,
            'order' => $data['order'] ?? 0,
        ]);

        app(AuditLogger::class)->log(
            'department.updated',
            $department->fresh(),
            $before,
            $department->only(['name', 'slug', 'description', 'parent_id', 'lead_user_id', 'order']),
        );

        return $department->fresh();
    }

    public function delete(Department $department): void
    {
        if ($department->children()->exists()) {
            throw ValidationException::withMessages([
                'department' => 'Remove or reassign child departments first.',
            ]);
        }

        $before = $department->only(['name', 'slug']);
        $department->delete();

        app(AuditLogger::class)->log('department.deleted', $department, $before, null);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'department';
        $slug = $base;
        $i = 2;

        while (
            Department::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
