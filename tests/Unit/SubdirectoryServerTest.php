<?php

require_once dirname(__DIR__, 2).'/bootstrap/fix-subdirectory.php';

it('normalizes SCRIPT_NAME when requests go through /public/index.php', function () {
    $fixed = oj_fix_subdirectory_server([
        'SCRIPT_NAME' => '/intranet/public/index.php',
        'REQUEST_URI' => '/intranet/install',
        'PHP_SELF' => '/intranet/public/index.php',
    ]);

    expect($fixed['SCRIPT_NAME'])->toBe('/intranet/index.php')
        ->and($fixed['REQUEST_URI'])->toBe('/intranet/install')
        ->and($fixed['PHP_SELF'])->toBe('/intranet/index.php');
});

it('strips /public from REQUEST_URI when present', function () {
    $fixed = oj_fix_subdirectory_server([
        'SCRIPT_NAME' => '/intranet/public/index.php',
        'REQUEST_URI' => '/intranet/public/install?step=1',
    ]);

    expect($fixed['SCRIPT_NAME'])->toBe('/intranet/index.php')
        ->and($fixed['REQUEST_URI'])->toBe('/intranet/install?step=1');
});

it('leaves Alias-style SCRIPT_NAME unchanged', function () {
    $fixed = oj_fix_subdirectory_server([
        'SCRIPT_NAME' => '/intranet/index.php',
        'REQUEST_URI' => '/intranet/install',
    ]);

    expect($fixed['SCRIPT_NAME'])->toBe('/intranet/index.php')
        ->and($fixed['REQUEST_URI'])->toBe('/intranet/install');
});
