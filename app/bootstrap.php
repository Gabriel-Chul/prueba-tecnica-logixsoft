<?php

$config = require __DIR__ . '/config.php';
$storageDir = $config['storage_dir'];

if (!is_dir($storageDir)) {
    mkdir($storageDir, 0750, true);
}

$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

session_name($config['session_name']);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Strict',
]);

session_start();

require_once __DIR__ . '/services/SecurityHeaders.php';
require_once __DIR__ . '/services/CsrfService.php';
require_once __DIR__ . '/services/RateLimiter.php';
require_once __DIR__ . '/services/UserStore.php';
require_once __DIR__ . '/services/AuthService.php';

$securityHeaders = new SecurityHeaders();
$securityHeaders->applyDefault();

function flash_set(string $key, string $value): void
{
    $_SESSION['flash'][$key] = $value;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $value = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    return $value;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}
