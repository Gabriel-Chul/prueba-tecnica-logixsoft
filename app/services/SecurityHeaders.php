<?php

class SecurityHeaders
{
    public function applyDefault(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none';");
    }
}
