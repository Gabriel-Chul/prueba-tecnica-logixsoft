<?php

require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/UserStore.php';

class AuthService
{
    private UserStore $users;
    private RateLimiter $rateLimiter;
    private RateLimiter $emailLimiter;
    private string $storageDir;

    public function __construct(UserStore $users, RateLimiter $rateLimiter, RateLimiter $emailLimiter, string $storageDir)
    {
        $this->users = $users;
        $this->rateLimiter = $rateLimiter;
        $this->emailLimiter = $emailLimiter;
        $this->storageDir = $storageDir;
    }

    public function register(string $name, string $email, string $password, string $confirm, string $ip): array
    {
        $errors = $this->validateRegister($name, $email, $password, $confirm);
        $key = 'register:' . $ip;

        if ($this->rateLimiter->tooManyAttempts($key)) {
            $errors[] = 'Demasiados intentos. Intenta mas tarde.';
        }

        if (!empty($errors)) {
            $this->rateLimiter->hit($key);
            $this->logEvent('register_fail', $ip);
            return ['ok' => false, 'errors' => $errors];
        }

        if ($this->users->findByEmail($email)) {
            $this->rateLimiter->hit($key);
            $this->logEvent('register_exists', $ip);
            return ['ok' => false, 'errors' => ['El correo ya esta registrado.']];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        $user = [
            'id' => bin2hex(random_bytes(8)),
            'name' => $name,
            'email' => $email,
            'passwordHash' => $hash,
            'createdAt' => date('c'),
        ];

        $this->users->addUser($user);
        $this->rateLimiter->clear($key);
        $this->logEvent('register_ok', $ip);

        return ['ok' => true];
    }

    public function login(string $email, string $password, string $ip): array
    {
        $errors = $this->validateLogin($email, $password);
        $key = 'login:' . $ip;
        $emailKey = $email !== '' ? 'login-email:' . $email : '';

        if ($this->rateLimiter->tooManyAttempts($key)) {
            $errors[] = 'Demasiados intentos. Intenta mas tarde.';
        }

        if ($emailKey !== '' && $this->emailLimiter->tooManyAttempts($emailKey)) {
            $errors[] = 'La cuenta esta temporalmente bloqueada.';
        }

        if (!empty($errors)) {
            $this->rateLimiter->hit($key);
            if ($emailKey !== '') {
                $this->emailLimiter->hit($emailKey);
            }
            $this->logEvent('login_fail', $ip);
            return ['ok' => false, 'errors' => $errors];
        }

        $user = $this->users->findByEmail($email);
        if (!$user || !password_verify($password, $user['passwordHash'])) {
            $this->rateLimiter->hit($key);
            if ($emailKey !== '') {
                $this->emailLimiter->hit($emailKey);
            }
            $this->logEvent('login_fail', $ip);
            return ['ok' => false, 'errors' => ['Credenciales invalidas.']];
        }

        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
        ];

        $this->rateLimiter->clear($key);
        if ($emailKey !== '') {
            $this->emailLimiter->clear($emailKey);
        }
        $this->logEvent('login_ok', $ip);

        return ['ok' => true];
    }

    private function validateRegister(string &$name, string &$email, string $password, string $confirm): array
    {
        $errors = [];

        $name = trim($name);
        $email = strtolower(trim($email));

        if ($name === '' || strlen($name) < 2 || strlen($name) > 60) {
            $errors[] = 'El nombre debe tener entre 2 y 60 caracteres.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Correo invalido.';
        }

        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors[] = 'La contrasena debe tener entre 8 y 72 caracteres.';
        }

        if ($password !== $confirm) {
            $errors[] = 'Las contrasenas no coinciden.';
        }

        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = 'La contrasena debe incluir una mayuscula y un numero.';
        }

        return $errors;
    }

    private function validateLogin(string &$email, string $password): array
    {
        $errors = [];
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Correo invalido.';
        }

        if ($password === '') {
            $errors[] = 'La contrasena es obligatoria.';
        }

        return $errors;
    }

    private function logEvent(string $event, string $ip): void
    {
        $filePath = rtrim($this->storageDir, '/\\') . '/audit.log';
        $timestamp = date('c');
        $payload = sprintf("%s\t%s\t%s", $timestamp, $ip, $event);
        $previousHash = $this->readLastHash($filePath);
        $hash = hash('sha256', $previousHash . $payload);
        $line = $payload . "\t" . $hash . "\n";
        $handle = fopen($filePath, 'a');
        if ($handle === false) {
            return;
        }

        flock($handle, LOCK_EX);
        fwrite($handle, $line);
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    private function readLastHash(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return '';
        }

        $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return '';
        }

        $last = $lines[count($lines) - 1];
        $parts = explode("\t", $last);
        return $parts[3] ?? '';
    }
}
