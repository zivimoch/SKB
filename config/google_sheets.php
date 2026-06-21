<?php

return [
    'enabled' => env('GOOGLE_SHEETS_ENABLED', false),
    'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID'),
    'service_log_sheet' => env('GOOGLE_SHEETS_SERVICE_LOG_SHEET', 'Log Layanan (Auto)'),
    'credentials_path' => env(
        'GOOGLE_SHEETS_CREDENTIALS_PATH',
        storage_path('app/private/google-service-account.json')
    ),
];
