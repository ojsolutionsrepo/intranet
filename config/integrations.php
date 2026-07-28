<?php

return [
    'sso' => [
        // local | jwt | google_oidc (local always remains available)
        'driver' => env('SSO_DRIVER', 'local'),
        'jwt_secret' => env('SSO_JWT_SECRET'),
        'google_client_id' => env('GOOGLE_OIDC_CLIENT_ID'),
        'google_client_secret' => env('GOOGLE_OIDC_CLIENT_SECRET'),
        'hosted_domain' => env('GOOGLE_OIDC_HOSTED_DOMAIN'),
    ],

    'drive' => [
        'enabled' => (bool) env('DRIVE_BROKER_ENABLED', false),
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID', env('GOOGLE_OIDC_CLIENT_ID')),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET', env('GOOGLE_OIDC_CLIENT_SECRET')),
        'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),
    ],

    'plane' => [
        // fake | http
        'driver' => env('PLANE_DRIVER', 'fake'),
        'base_url' => env('PLANE_BASE_URL'),
        'api_key' => env('PLANE_API_KEY'),
    ],

    'governex' => [
        // csv | api
        'driver' => env('GOVERNEX_DRIVER', 'csv'),
        'base_url' => env('GOVERNEX_BASE_URL'),
        'api_key' => env('GOVERNEX_API_KEY'),
        'csv_path' => env('GOVERNEX_CSV_PATH'),
    ],

    'projects' => [
        'staleness_minutes' => (int) env('PROJECT_STALENESS_MINUTES', 60),
        'sync_interval_minutes' => 15,
    ],
];
