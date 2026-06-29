<?php
declare(strict_types=1);

namespace App\Support;

final class SampleCode
{
    /** Fyzicky identifikator vzorku, napr. SMP-2026-1A2B3C4D. */
    public static function sampleId(?int $year = null): string
    {
        $year ??= (int) date('Y');
        return 'SMP-' . $year . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /** URL-safe token (base64url, ~192 bitu entropie). Do DB jen jeho hash. */
    public static function token(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    }
}
