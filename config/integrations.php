<?php

return [
    'clock_skew_seconds' => (int) env('INTEGRATION_CLOCK_SKEW_SECONDS', 300),
    'nonce_ttl_seconds' => (int) env('INTEGRATION_NONCE_TTL_SECONDS', 600),

    'clients' => [
        env('MOKA_INTEGRATION_KEY_ID', 'moka-v1') => [
            'name' => 'MokaV2',
            'source_system' => 'mokav2',
            'institution_code' => 'uptd-ppa-dki',
            'institution_name' => 'UPTD PPA DKI Jakarta',
            'environment' => env('APP_ENV') === 'production' ? 'production' : 'sandbox',
            'scopes' => [
                'connection:test',
                'cases:read',
                'cases:write',
                'assessments:write',
                'interventions:read',
                'interventions:write',
            ],
            'secret' => env('MOKA_INTEGRATION_SECRET'),
            'previous_secret' => env('MOKA_INTEGRATION_PREVIOUS_SECRET'),
            'active' => env('MOKA_INTEGRATION_ACTIVE', true),
        ],
    ],
];
