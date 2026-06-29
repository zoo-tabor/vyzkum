<?php
declare(strict_types=1);

namespace App\Support;

final class FormConditions
{
    /**
     * Je otazka viditelna podle config_json (visible_if) a dosud zadanych odpovedi?
     *
     * @param array<string, mixed>|null $config dekodovany config_json
     * @param array<string, mixed> $answersByKey question_key => hodnota (string|array)
     */
    public static function isVisible(?array $config, array $answersByKey): bool
    {
        $visibleIf = $config['visible_if'] ?? null;
        if (!is_array($visibleIf) || !isset($visibleIf['q'], $visibleIf['eq'])) {
            return true;
        }

        $controlling = $answersByKey[$visibleIf['q']] ?? null;
        $expected = (string) $visibleIf['eq'];

        if (is_array($controlling)) {
            return in_array($expected, array_map('strval', $controlling), true);
        }
        return (string) $controlling === $expected;
    }
}
