<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Popisky stavu vzorku. Kanonicky se v DB drzi ANGLICKY KOD (samples.status),
 * UI zobrazi stitek v jazyce diveka (fallback cs). Kody drzet v souladu s
 * SampleController::STATUSES (validace/nabidka).
 */
final class SampleStatus
{
    /** @var array<string, string> kod => cesky popisek (zdroj/fallback) */
    public const LABELS = [
        'created' => 'Vytvořeno',
        'assigned_to_vet' => 'Přiřazeno veterináři',
        'vet_submitted' => 'Odesláno veterinářem',
        'owner_submitted' => 'Odesláno majitelem',
        'sample_received' => 'Vzorek přijat',
        'data_validated' => 'Data ověřena',
        'analysis_done' => 'Analýza dokončena',
    ];

    /** Prelozeny stitek stavu; prazdny vstup => '', neznamy kod => samotny kod. */
    public static function label(?string $code): string
    {
        $c = trim((string) $code);
        if ($c === '') {
            return '';
        }
        return I18n::td('sample_status', $c, self::LABELS[$c] ?? $c);
    }
}
