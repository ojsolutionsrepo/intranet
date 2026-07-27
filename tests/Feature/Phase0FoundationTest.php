<?php

use App\Core\Hooks\Hook;
use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use App\Shared\Models\AuditLog;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('registers the demo module and shows it in the menu when enabled', function () {
    $registry = app(ModuleRegistry::class);

    expect($registry->get('demo'))->not->toBeNull()
        ->and($registry->isEnabled('demo'))->toBeTrue();

    $labels = collect(app(ModuleRegistry::class)->menuItems())->pluck('label');
    expect($labels)->toContain('Demo');
});

it('returns 404 for demo when the module is disabled', function () {
    DB::table('modules')->where('name', 'demo')->update(['is_enabled' => false]);
    app(ModuleRegistry::class)->forgetCache('demo');

    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    expect(collect(app(ModuleRegistry::class)->menuItems())->pluck('label'))->not->toContain('Demo');

    $this->actingAs($staff)
        ->get('/demo')
        ->assertNotFound();
});

it('fires hook actions and applies filters', function () {
    $called = false;
    Hook::addAction('test.ping', function () use (&$called) {
        $called = true;
    });
    Hook::action('test.ping');
    expect($called)->toBeTrue();

    Hook::addFilter('test.value', fn (int $v) => $v + 1);
    expect(Hook::filter('test.value', 41))->toBe(42);
});

it('seeds Admin Manager Staff and Guest roles', function () {
    expect(Role::query()->pluck('name')->all())
        ->toContain('Admin', 'Manager', 'Staff', 'Guest');
});

it('allows staff to log in with email and password', function () {
    $this->post('/login', [
        'email' => 'staff@oj.local',
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticated();
});

it('records a login in the audit log', function () {
    $this->post('/login', [
        'email' => 'staff@oj.local',
        'password' => 'password',
    ]);

    expect(AuditLog::query()->where('action', 'login')->exists())->toBeTrue();
});

it('forbids staff from accessing admin', function () {
    $staff = User::query()->where('email', 'staff@oj.local')->firstOrFail();

    $this->actingAs($staff)
        ->get('/admin')
        ->assertForbidden();
});

it('allows admin to access admin', function () {
    $admin = User::query()->where('email', 'admin@oj.local')->firstOrFail();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertOk();
});
