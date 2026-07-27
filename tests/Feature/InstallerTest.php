<?php

use App\Models\User;
use App\Shared\Services\Installer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    File::delete(storage_path('app/'.Installer::LOCK_FILE));
});

it('redirects the app to the installer when not installed', function () {
    $this->get('/')->assertRedirect(route('install.requirements'));
});

it('shows requirement checks on the first step', function () {
    $this->get(route('install.requirements'))
        ->assertOk()
        ->assertSee('Server requirements')
        ->assertSee('PHP 8.2+');
});

it('blocks the database step until requirements are confirmed', function () {
    $this->get(route('install.database'))
        ->assertRedirect(route('install.requirements'));
});

it('completes installation with sqlite and an admin account', function () {
    $installer = app(Installer::class);
    expect($installer->requirementsPassed())->toBeTrue();

    $this->withSession(['install.requirements_ok' => true])
        ->post(route('install.database.store'), [
            'connection' => 'sqlite',
        ])
        ->assertRedirect(route('install.admin'));

    $this->withSession([
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

    $this->get(route('install.requirements'))->assertRedirect(route('login'));
});

it('locks the installer after install', function () {
    app(Installer::class)->markInstalled();

    $this->get(route('install.requirements'))->assertRedirect(route('login'));
});
