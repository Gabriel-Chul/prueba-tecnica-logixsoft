<?php

return [
    'storage_dir' => dirname(__DIR__) . '/storage',
    'session_name' => 'pt_session',
    'csrf_key' => 'csrf_token',
    'rate_limit' => [
        'window_seconds' => 300,
        'max_attempts' => 5,
    ],
    'password' => [
        'min_length' => 8,
        'max_length' => 72,
    ],
];
