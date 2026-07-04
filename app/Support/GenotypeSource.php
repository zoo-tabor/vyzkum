<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Zdroj genotypu (biologicky) - sekvenace vs GWAS. Vychozi je sekvenace,
 * protoze tak se vetsinou testuje.
 */
final class GenotypeSource
{
    public const DEFAULT = 'sekvenace';

    /** @var array<string, string> interni hodnota => popisek */
    public const LABELS = [
        'sekvenace' => 'Sekvenace',
        'GWAS' => 'GWAS',
    ];

    /** Vrati validni hodnotu, jinak null. */
    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);
        return isset(self::LABELS[$value]) ? $value : null;
    }

    /** Popisek jedne hodnoty; prazdna/neznama => "-". */
    public static function label(?string $value): string
    {
        $value = trim((string) $value);
        return self::LABELS[$value] ?? ($value === '' ? '-' : $value);
    }

    /**
     * Popisek pro seznam hodnot oddelenych carkou (napr. z GROUP_CONCAT DISTINCT).
     */
    public static function labelList(?string $csv): string
    {
        $csv = trim((string) $csv);
        if ($csv === '') {
            return '-';
        }
        $parts = [];
        foreach (explode(',', $csv) as $p) {
            $p = trim($p);
            if ($p !== '') {
                $parts[] = self::LABELS[$p] ?? $p;
            }
        }
        return $parts === [] ? '-' : implode(', ', $parts);
    }

    /** @return array<string, string> nabidka pro <select> */
    public static function options(): array
    {
        return self::LABELS;
    }
}
