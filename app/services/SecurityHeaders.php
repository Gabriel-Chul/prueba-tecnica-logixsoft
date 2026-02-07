<?php

class SecurityHeaders
{
    public function applyDefault(bool $https): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: geolocation=(self)');
        header('Cross-Origin-Resource-Policy: same-origin');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('X-Permitted-Cross-Domain-Policies: none');
        if ($https) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        header("Content-Security-Policy: default-src 'self'; object-src 'none'; style-src 'self' 'unsafe-inline' https://unpkg.com; script-src 'self' https://unpkg.com; img-src 'self' data: https://*.tile.openstreetmap.org; connect-src 'self' https://unpkg.com; base-uri 'self'; form-action 'self'; frame-ancestors 'none';");
    }
}
