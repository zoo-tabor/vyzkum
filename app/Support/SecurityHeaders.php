<?php
declare(strict_types=1);

namespace App\Support;

final class SecurityHeaders
{
    /**
     * Bezpecnostni HTTP hlavicky. CSP povoluje 'unsafe-inline' kvuli inline
     * skriptum/stylum a event handlerum (onchange/onclick) ve vlastnich sablonach;
     * externi zdroje nejsou povolene (vse je self).
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'same-origin',
            'Content-Security-Policy' =>
                "default-src 'self'; "
                . "img-src 'self' data:; "
                . "style-src 'self' 'unsafe-inline'; "
                . "script-src 'self' 'unsafe-inline'; "
                . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'",
        ];
    }
}
