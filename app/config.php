<?php

return [
    'storage_dir' => dirname(__DIR__) . '/storage',
    'session_name' => 'pt_session',
    'csrf_key' => 'csrf_token',
    'rate_limit' => [
        'window_seconds' => 300,
        'max_attempts' => 20,
    ],
    'rate_limit_email' => [
        'window_seconds' => 900,
        'max_attempts' => 20,
    ],
    'rate_limit_search' => [
        'window_seconds' => 60,
        'max_attempts' => 10,
    ],
    'encryption_key' => 'base64:mYzqEPs8L0o+Jm6eOXy27L5Y0zGd31O/6B2d8YStq9g=',
    'password' => [
        'min_length' => 8,
        'max_length' => 72,
    ],
];
