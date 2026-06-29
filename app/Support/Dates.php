<?php
declare(strict_types=1);

namespace App\Support;

final class Dates
{
    /** "DD.MM.RRRR" -> "YYYY-MM-DD" nebo null kdyz neplatne/prazdne. */
    public static function fromCz(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (!preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $value, $m)) {
            return null;
        }
        [, $d, $mo, $y] = $m;
        if (!checkdate((int) $mo, (int) $d, (int) $y)) {
            return null;
        }
        return sprintf('%04d-%02d-%02d', (int) $y, (int) $mo, (int) $d);
    }

    /** "YYYY-MM-DD" -> "DD.MM.RRRR" (prazdny vstup -> ""). */
    public static function toCz(?string $iso): string
    {
        if ($iso === null || trim($iso) === '') {
            return '';
        }
        $d = \DateTime::createFromFormat('Y-m-d', substr($iso, 0, 10));
        return $d !== false ? $d->format('d.m.Y') : '';
    }
}
