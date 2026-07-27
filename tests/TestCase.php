<?php

namespace Tests;

use App\Shared\Services\Installer;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Installer::isInstalled()) {
            app(Installer::class)->markInstalled();
        }
    }
}
