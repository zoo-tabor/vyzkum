<?php
declare(strict_types=1);

namespace App\Support;

final class Phone
{
    /** Zobrazeni telefonu s mezinarodni predvolbou: vedouci "00" -> "+". */
    public static function formatCz(?string $phone): string
    {
        $p = trim((string) $phone);
        if ($p === '') {
            return '';
        }
        if (str_starts_with($p, '00')) {
            return '+' . substr($p, 2);
        }
        return $p;
    }
}
