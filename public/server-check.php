<?php

/**
 * Standalone XAMPP diagnostics — open http://localhost/intranet/server-check.php
 * when the app returns Apache 500 and Laravel never boots.
 * Delete this file after go-live if you prefer.
 */
header('Content-Type: text/plain; charset=utf-8');

$root = dirname(__DIR__);
$lines = [];

$lines[] = 'OJ Intranet — server check';
$lines[] = str_repeat('-', 40);
$lines[] = 'PHP: '.PHP_VERSION.((version_compare(PHP_VERSION, '8.2.0', '>=')) ? ' OK' : ' NEED 8.2+');
$lines[] = 'SAPI: '.PHP_SAPI;
$lines[] = 'Document: '.(__FILE__);
$lines[] = 'SCRIPT_NAME: '.($_SERVER['SCRIPT_NAME'] ?? '');
$lines[] = 'REQUEST_URI: '.($_SERVER['REQUEST_URI'] ?? '');

$vendor = is_file($root.'/vendor/autoload.php');
$lines[] = 'vendor/autoload.php: '.($vendor ? 'OK' : 'MISSING — run composer install');

$env = is_file($root.'/.env');
$lines[] = '.env: '.($env ? 'OK' : 'MISSING — copy .env.example to .env');

$key = '';
if ($env) {
    foreach (file($root.'/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (str_starts_with($line, 'APP_KEY=')) {
            $key = substr($line, 8);
            break;
        }
    }
}
$lines[] = 'APP_KEY: '.($key !== '' && $key !== 'null' ? 'set' : 'EMPTY — php artisan key:generate');

$lock = is_file($root.'/storage/app/installed');
$lines[] = 'install lock (storage/app/installed): '.($lock ? 'PRESENT (installer skipped)' : 'absent (installer should run)');
if ($lock) {
    $lines[] = '  To force installer: delete storage/app/installed';
}

$storage = is_writable($root.'/storage') && is_writable($root.'/bootstrap/cache');
$lines[] = 'writable storage + bootstrap/cache: '.($storage ? 'OK' : 'FIX permissions');

$lines[] = '';
$lines[] = 'If this page loads but /login is Apache 500, check:';
$lines[] = '  C:\\xampp\\apache\\logs\\error.log';
$lines[] = '  Look for AH00124 (rewrite loop) → include apache/alias.conf and restart Apache';
$lines[] = '  Look for PHP Fatal → PHP version / missing extension / vendor';

echo implode("\n", $lines)."\n";
