<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('intranet:sync-integrations')->everyFifteenMinutes();
Schedule::command('intranet:gdpr-prune')->dailyAt('02:30');
