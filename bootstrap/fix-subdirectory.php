<?php

/**
 * Normalize $_SERVER for XAMPP subdirectory installs when the Apache Alias
 * is missing and requests are rewritten through /public/.
 *
 * Without this, Laravel may see path "intranet/install" instead of "install"
 * and return 404 on the installer and every other route.
 *
 * @param  array<string, mixed>  $server
 * @return array<string, mixed>
 */
function oj_fix_subdirectory_server(array $server): array
{
    $script = str_replace('\\', '/', (string) ($server['SCRIPT_NAME'] ?? ''));

    if (str_ends_with($script, '/public/index.php')) {
        $server['SCRIPT_NAME'] = substr($script, 0, -strlen('/public/index.php')).'/index.php';
    }

    foreach (['REQUEST_URI', 'PHP_SELF', 'SCRIPT_URL'] as $key) {
        if (empty($server[$key]) || ! is_string($server[$key])) {
            continue;
        }

        $server[$key] = preg_replace('#/public(?=/|\?|$)#', '', $server[$key], 1) ?? $server[$key];
    }

    return $server;
}
