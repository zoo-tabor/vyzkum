<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Stavy GWAS a jejich UI popisky. Hodnota se drzi na vzorku (samples.gwas_status).
 */
final class Gwas
{
    /** @var array<string, string> interni kod => popisek do UI */
    public const LABELS = [
        'GWAS_sent' => 'Odesláno',
        'GWAS_failed' => 'Nevyšlo',
        'GWAS_ok' => 'Vyšlo',
        'GWAS_none' => 'Ne',
    ];

    /** Popisek stavu; prazdna/neznama hodnota => "-". */
    public static function label(?string $status): string
    {
        $s = trim((string) $status);
        return self::LABELS[$s] ?? '-';
    }

    /**
     * Nabidka pro <select>: prazdna volba + vsechny stavy.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return ['' => '- neuvedeno -'] + self::LABELS;
    }
}
