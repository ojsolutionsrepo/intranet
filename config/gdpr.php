<?php

return [
    'scanner' => env('VIRUS_SCANNER', 'null'), // null|clamav
    'clamav_host' => env('CLAMAV_HOST', '127.0.0.1'),
    'clamav_port' => (int) env('CLAMAV_PORT', 3310),

    'retention_days' => [
        'audit_logs' => (int) env('GDPR_AUDIT_RETENTION_DAYS', 730),
        'sessions' => (int) env('GDPR_SESSION_RETENTION_DAYS', 30),
        'search_zero_results' => (int) env('GDPR_SEARCH_ZERO_RETENTION_DAYS', 90),
        'sso_jtis' => (int) env('GDPR_SSO_JTI_RETENTION_DAYS', 7),
    ],

    'privacy_contact' => env('GDPR_PRIVACY_CONTACT', 'privacy@oj.local'),
];
