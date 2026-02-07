<?php

class SecurityHeaders
{
    public function applyDefault(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header("Content-Security-Policy: default-src 'self'; style-src 'self' https://unpkg.com; script-src 'self' https://unpkg.com; img-src 'self' data: https://*.tile.openstreetmap.org; connect-src 'self' https://unpkg.com; base-uri 'self'; form-action 'self'; frame-ancestors 'none';");
    }
}
