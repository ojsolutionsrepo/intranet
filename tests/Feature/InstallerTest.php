<?php

use App\Models\User;
use App\Shared\Services\Installer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

beforeEach(function () {
    File::delete(storage_path('app/'.Installer::LOCK_FILE));
});

it('redirects the app to the installer when not installed', function () {
    get('/')->assertRedirect(route('install.requirements'));
});

it('shows requirement checks on the first step', function () {
    get(route('install.requirements'))
        ->assertOk()
        ->assertSee('Server requirements')
        ->assertSee('PHP 8.2+')
        ->assertSee('Apache Alias');
});

it('shows mysql credential fields without depending on a CDN', function () {
    withSession(['install.requirements_ok' => true])
        ->get(route('install.database'))
        ->assertOk()
        ->assertSee('name="host"', false)
        ->assertSee('name="database"', false)
        ->assertSee('name="username"', false)
        ->assertDontSee('cdn.jsdelivr.net')
        ->assertDontSee('alpinejs');
});

it('blocks the database step until requirements are confirmed', function () {
    get(route('install.database'))
        ->assertRedirect(route('install.requirements'));
});

it('completes installation with sqlite and an admin account', function () {
    $installer = app(Installer::class);
    expect($installer->requirementsPassed())->toBeTrue();

    withSession(['install.requirements_ok' => true])
        ->post(route('install.database.store'), [
            'connection' => 'sqlite',
        ])
        ->assertRedirect(route('install.admin'))
        ->assertSessionHas('install.database_ok', true);

    withSession([
        'install.requirements_ok' => true,
        'install.database_ok' => true,
    ])
        ->post(route('install.admin.store'), [
            'site_name' => 'OJ Test Intranet',
            'name' => 'Install Admin',
            'email' => 'install-admin@oj.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertRedirect(route('install.complete'));

    expect(Installer::isInstalled())->toBeTrue();

    $admin = User::query()->where('email', 'install-admin@oj.local')->first();
    expect($admin)->not->toBeNull()
        ->and($admin->hasRole('Admin'))->toBeTrue()
        ->and(Hash::check('password123', $admin->password))->toBeTrue();

    get(route('install.requirements'))->assertRedirect(route('login'));
});

it('optionally seeds demo content during install', function () {
    withSession([
        'install.requirements_ok' => true,
        'install.database_ok' => true,
    ])
        ->post(route('install.admin.store'), [
            'site_name' => 'OJ Demo Intranet',
            'name' => 'Demo Admin',
            'email' => 'demo-admin@oj.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'seed_demo' => '1',
        ])
        ->assertRedirect(route('install.complete'))
        ->assertSessionHas('install.seeded_demo', true);

    expect(Installer::isInstalled())->toBeTrue()
        ->and(User::query()->where('email', 'staff@oj.local')->exists())->toBeTrue()
        ->and(User::query()->where('email', 'demo-admin@oj.local')->exists())->toBeTrue();

    get(route('install.complete'))
        ->assertOk()
        ->assertSee('Demo data was loaded');
});

it('reports apache alias advisory without blocking install', function () {
    $_SERVER['OJ_SERVED_VIA_PUBLIC'] = '1';

    $check = app(Installer::class)->apacheUrlCheck();

    expect($check['id'])->toBe('apache_alias')
        ->and($check['ok'])->toBeTrue()
        ->and($check['advisory'] ?? false)->toBeTrue()
        ->and($check['detail'])->toContain('fallback')
        ->and($check['hint'])->toContain('alias.conf');

    expect(app(Installer::class)->requirementsPassed())->toBeTrue();
});

it('locks the installer after install', function () {
    app(Installer::class)->markInstalled();

    get(route('install.requirements'))->assertRedirect(route('login'));
});
