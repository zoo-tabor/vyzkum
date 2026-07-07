<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Popisky typu zdravotni udalosti. Kanonicky se v DB drzi ANGLICKY KOD
 * (health_events.event_type), UI zobrazi stitek v jazyce diveka (fallback cs).
 * Kody drzet v souladu s HealthEventRepository::TYPES.
 */
final class HealthEventType
{
    /** @var array<string, string> kod => cesky popisek (zdroj/fallback) */
    public const LABELS = [
        'disease' => 'Nemoc',
        'examination' => 'Vyšetření',
        'castration' => 'Kastrace',
        'death' => 'Úmrtí',
        'other' => 'Jiné',
    ];

    /** Prelozeny stitek typu; prazdny vstup => '', neznamy kod => samotny kod. */
    public static function label(?string $code): string
    {
        $c = trim((string) $code);
        if ($c === '') {
            return '';
        }
        return I18n::td('health_event_types', $c, self::LABELS[$c] ?? $c);
    }
}
