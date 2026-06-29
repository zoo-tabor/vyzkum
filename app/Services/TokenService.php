<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Bezpecne tokeny pro odkazy (pozvanky, reset hesla). Do DB jde jen hash.
 */
final class TokenService
{
    /** Vraci raw token (do odkazu) - min. 256 bitu entropie. */
    public static function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    /** Hash pro ulozeni do DB (token v odkazu se nikdy neuklada v plaintextu). */
    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
