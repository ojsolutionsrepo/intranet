<?php

use App\Models\User;
use App\Shared\Models\Department;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RoleSeeder::class);
});

it('lets admins open the departments admin page', function () {
    $admin = User::factory()->create(['email' => 'dept-admin@oj.local']);
    $admin->assignRole('Admin');
    $admin->givePermissionTo('admin.departments.manage');

    actingAs($admin)
        ->get(route('admin.departments'))
        ->assertOk()
        ->assertSee('Departments')
        ->assertSee('Add department');
});

it('blocks staff from the departments admin page', function () {
    $staff = User::factory()->create(['email' => 'dept-staff@oj.local']);
    $staff->assignRole('Staff');

    actingAs($staff)
        ->get(route('admin.departments'))
        ->assertForbidden();
});

it('UR-DIR-01 admin can create and update departments', function () {
    $admin = User::factory()->create(['email' => 'dept-crud@oj.local']);
    $admin->assignRole('Admin');
    $admin->givePermissionTo('admin.departments.manage');

    actingAs($admin);

    Livewire::test(\App\Modules\Admin\Livewire\DepartmentManager::class)
        ->set('name', 'People & Culture')
        ->set('description', 'HR and culture')
        ->set('order', 10)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('People & Culture');

    $department = Department::query()->where('slug', 'people-culture')->first();
    expect($department)->not->toBeNull()
        ->and($department->description)->toBe('HR and culture');

    Livewire::test(\App\Modules\Admin\Livewire\DepartmentManager::class)
        ->call('edit', $department->id)
        ->set('name', 'People')
        ->set('description', 'Updated')
        ->call('save')
        ->assertHasNoErrors();

    expect($department->fresh()->name)->toBe('People')
        ->and($department->fresh()->description)->toBe('Updated');
});

it('shows departments card on the admin dashboard', function () {
    $admin = User::factory()->create(['email' => 'dept-dash@oj.local']);
    $admin->assignRole('Admin');

    actingAs($admin)
        ->get(route('admin.index'))
        ->assertOk()
        ->assertSee('Departments')
        ->assertSee(route('admin.departments'), false);
});
