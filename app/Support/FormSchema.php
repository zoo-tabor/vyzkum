<?php
declare(strict_types=1);

namespace App\Support;

final class FormSchema
{
    /** typ => popisek pro UI */
    public const TYPES = [
        'short_text' => 'Krátká odpověď',
        'long_text' => 'Dlouhá odpověď',
        'single_choice' => 'Jedna volba (radio)',
        'multiple_choice' => 'Více voleb (checkbox)',
        'number' => 'Číslo',
        'date' => 'Datum',
        'yes_no' => 'Ano / Ne',
        'file' => 'Soubor',
        'disease_history' => 'Zdravotní historie (nemoci s obdobím)',
        'death_cause' => 'Příčina úmrtí (datum + číselník)',
    ];

    public static function isValidType(string $type): bool
    {
        return isset(self::TYPES[$type]);
    }

    /**
     * Typ, ktery nema vlastni moznosti ani volny vstup, ale strukturovanou zdravotni
     * historii z ciselniku nemoci (disease vetev death_causes) + obdobi od-do.
     */
    public static function isDiseaseHistory(string $type): bool
    {
        return $type === 'disease_history';
    }

    /**
     * Vestavena otazka "pricina umrti": datum umrti + kaskadovy vyber z ciselniku
     * death_causes. Pri odeslani zaklada umrti pres DogRepository::setAliveStatus
     * (dogs + dog_death_reports + health_event death) - jediny "death primitiv".
     */
    public static function isDeathCause(string $type): bool
    {
        return $type === 'death_cause';
    }

    /** Stitek typu otazky v jazyce diveka (fallback = cesky zdroj z TYPES). */
    public static function typeLabel(string $type): string
    {
        return I18n::td('form_types', $type, self::TYPES[$type] ?? $type);
    }

    public static function needsOptions(string $type): bool
    {
        return in_array($type, ['single_choice', 'multiple_choice'], true);
    }

    private const TRANSLIT = [
        'á' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e', 'í' => 'i', 'ň' => 'n',
        'ó' => 'o', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ú' => 'u', 'ů' => 'u', 'ý' => 'y', 'ž' => 'z',
        'Á' => 'a', 'Č' => 'c', 'Ď' => 'd', 'É' => 'e', 'Ě' => 'e', 'Í' => 'i', 'Ň' => 'n',
        'Ó' => 'o', 'Ř' => 'r', 'Š' => 's', 'Ť' => 't', 'Ú' => 'u', 'Ů' => 'u', 'Ý' => 'y', 'Ž' => 'z',
    ];

    public static function slug(string $value): string
    {
        // Deterministicky (nezavisle na platforme/iconv) - nejprve ceska diakritika.
        $value = strtr($value, self::TRANSLIT);
        $value = strtolower($value);
        $value = (string) preg_replace('/[^a-z0-9]+/', '_', $value);
        $value = trim($value, '_');
        return $value === '' ? 'q' : substr($value, 0, 64);
    }

    /**
     * Parsuje moznosti: jeden radek = jedna moznost, bud "label" nebo "key|label".
     * Klice se slugifikuji a deduplikuji.
     *
     * @return array<int, array{key: string, label: string}>
     */
    public static function parseOptions(string $text): array
    {
        $out = [];
        $seen = [];
        foreach (preg_split('/\r?\n/', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (str_contains($line, '|')) {
                [$k, $l] = explode('|', $line, 2);
                $label = trim($l);
                $key = self::slug(trim($k));
            } else {
                $label = $line;
                $key = self::slug($line);
            }
            if ($label === '') {
                continue;
            }
            $base = $key;
            $i = 2;
            while (isset($seen[$key])) {
                $key = $base . '_' . $i;
                $i++;
            }
            $seen[$key] = true;
            $out[] = ['key' => $key, 'label' => $label];
        }
        return $out;
    }
}
