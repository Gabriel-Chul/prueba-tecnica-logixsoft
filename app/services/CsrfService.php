<?php

class CsrfService
{
    private string $sessionKey;

    public function __construct(string $sessionKey)
    {
        $this->sessionKey = $sessionKey;
    }

    public function token(): string
    {
        if (empty($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = bin2hex(random_bytes(32));
        }

        return $_SESSION[$this->sessionKey];
    }

    public function validate(?string $token): bool
    {
        if (empty($token) || empty($_SESSION[$this->sessionKey])) {
            return false;
        }

        return hash_equals($_SESSION[$this->sessionKey], $token);
    }
}
