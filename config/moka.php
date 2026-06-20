<?php

return [
    'base_url' => rtrim(env('MOKA_API_BASE_URL', 'http://127.0.0.1:8000'), '/'),
    'key_id' => env('MOKA_API_KEY_ID', 'skb-v1'),
    'secret' => env('MOKA_API_SECRET'),
    'timeout' => (int) env('MOKA_API_TIMEOUT', 30),
    'verify_tls' => env('MOKA_API_VERIFY_TLS', true),
];
