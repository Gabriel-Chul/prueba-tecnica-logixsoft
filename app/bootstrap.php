<?php

$config = require __DIR__ . '/config.php';
$storageDir = $config['storage_dir'];

if (!is_dir($storageDir)) {
    mkdir($storageDir, 0750, true);
}

$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $https ? '1' : '0');
ini_set('session.cookie_samesite', 'Strict');

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
$securityHeaders->applyDefault($https);

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

function is_same_origin_request(): bool
{
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return true;
    }

    $hostLower = strtolower($host);
    if (strpos($hostLower, 'localhost') === 0 || strpos($hostLower, '127.0.0.1') === 0) {
        return true;
    }

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';

    if ($origin === '' && $referer === '') {
        return true;
    }

    $allowedHosts = [$host, 'localhost', '127.0.0.1'];
    $check = $origin !== '' ? $origin : $referer;
    $parts = parse_url($check);
    if (!is_array($parts) || empty($parts['host'])) {
        return false;
    }

    $checkHost = $parts['host'];
    return in_array($checkHost, $allowedHosts, true);
}
