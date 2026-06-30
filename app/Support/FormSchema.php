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
    ];

    public static function isValidType(string $type): bool
    {
        return isset(self::TYPES[$type]);
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
