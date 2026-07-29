<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// XAMPP subdirectory without Alias: rewrite via /public/ confuses SCRIPT_NAME.
require __DIR__.'/../bootstrap/fix-subdirectory.php';
$_SERVER['OJ_SERVED_VIA_PUBLIC'] = str_ends_with(
    str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '')),
    '/public/index.php'
) ? '1' : '0';
$_SERVER = oj_fix_subdirectory_server($_SERVER);

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
