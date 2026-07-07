<?php
declare(strict_types=1);

namespace App\Support;

use App\Repositories\BreedRepository;
use App\Repositories\TranslationRepository;

/**
 * Preklad nazvu plemene do jazyka diveka. Kanonicky (cesky) nazev zustava v
 * tabulce breeds; preklad je v tabulce translations (entity_type 'breed', field
 * 'name'), editovatelny z admin UI. Overlay se dela podle NAZVU (mapuje se na id),
 * takze prejmenovani plemena preklad nerozbije (klic prekladu = id).
 */
final class Breeds
{
    public const ENTITY = 'breed';

    /** @var array<string, string>|null cache: cesky nazev => zobrazovany nazev (pro aktualni locale) */
    private static ?array $cache = null;

    /** @var string|null locale, pro ktere je cache postavena */
    private static ?string $cacheLocale = null;

    /** Prelozeny nazev plemene (fallback = cesky nazev z DB). */
    public static function translate(?string $name): string
    {
        $name = (string) $name;
        if ($name === '' || I18n::locale() === I18n::defaultLocale()) {
            return $name;
        }
        return self::cache()[$name] ?? $name;
    }

    /** @return array<string, string> */
    private static function cache(): array
    {
        $locale = I18n::locale();
        if (self::$cache !== null && self::$cacheLocale === $locale) {
            return self::$cache;
        }

        $breeds = (new BreedRepository())->all(false);
        $ids = [];
        foreach ($breeds as $b) {
            $ids[] = (int) $b['id'];
        }
        $tx = (new TranslationRepository())->allForFields(self::ENTITY, ['name'], $ids, $locale);

        $map = [];
        foreach ($breeds as $b) {
            $id = (int) $b['id'];
            $czech = (string) $b['name'];
            $translated = $tx[$id]['name'] ?? '';
            $map[$czech] = ($translated !== '') ? $translated : $czech;
        }

        self::$cache = $map;
        self::$cacheLocale = $locale;
        return $map;
    }

    /** Test seam. */
    public static function flush(): void
    {
        self::$cache = null;
        self::$cacheLocale = null;
    }
}
