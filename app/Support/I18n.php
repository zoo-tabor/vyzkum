<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Lehky i18n bez zavislosti. Cesky text = klic (gettext styl). Chybejici preklad
 * spadne zpet na cesky zdroj, takze aplikace nikdy neukaze prazdno. Kontext (tc)
 * resi homonyma - stejny cesky text s ruznym prekladem podle mista.
 */
final class I18n
{
    public const DEFAULT = 'cs';
    private const SEP = "\x04"; // oddelovac kontext/text (jako gettext msgctxt)

    private static string $locale = self::DEFAULT;
    /** @var array<string, string>|null nactenej katalog pro aktualni locale */
    private static ?array $catalog = null;
    /** @var array<string, string>|null cache registru */
    private static ?array $available = null;
    /** @var array<string, array<string, string>> cache domenovych katalogu: "domena|locale" => mapa */
    private static array $domains = [];

    public static function defaultLocale(): string
    {
        return self::DEFAULT;
    }

    /** @return array<string, array{name:string, flag:string}> kod => meta */
    public static function available(): array
    {
        if (self::$available === null) {
            $file = self::root() . '/resources/lang/locales.php';
            $data = is_file($file) ? require $file : [];
            self::$available = (is_array($data) && $data !== [])
                ? $data
                : [self::DEFAULT => ['name' => 'Čeština', 'flag' => 'cz']];
        }
        return self::$available;
    }

    public static function isValid(string $locale): bool
    {
        return isset(self::available()[$locale]);
    }

    /** Nazev jazyka v jeho originalnim jazyce. */
    public static function name(string $locale): string
    {
        $meta = self::available()[$locale] ?? null;
        return is_array($meta) ? (string) ($meta['name'] ?? $locale) : $locale;
    }

    /** Kod vlajky (soubor public/assets/flags/<flag>.svg). */
    public static function flag(string $locale): string
    {
        $meta = self::available()[$locale] ?? null;
        return is_array($meta) ? (string) ($meta['flag'] ?? '') : '';
    }

    public static function setLocale(string $locale): void
    {
        $locale = self::isValid($locale) ? $locale : self::DEFAULT;
        if ($locale === self::$locale && self::$catalog !== null) {
            return;
        }
        self::$locale = $locale;
        self::$catalog = self::loadCatalog($locale);
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    /**
     * Preklad. Cesky $text je zdroj i klic; chybejici preklad -> $text.
     *
     * @param array<string, string|int|float> $params nahrazuji se {klic}
     */
    public static function t(string $text, array $params = []): string
    {
        return self::render(self::lookup($text) ?? $text, $params);
    }

    /**
     * Preklad s kontextem (resi homonyma). Zkusi 'kontext\x04text', pak holy text,
     * nakonec cesky zdroj.
     *
     * @param array<string, string|int|float> $params
     */
    public static function tc(string $context, string $text, array $params = []): string
    {
        $value = self::lookup($context . self::SEP . $text) ?? self::lookup($text) ?? $text;
        return self::render($value, $params);
    }

    /**
     * Preklad domenoveho ciselniku (napr. pricin umrti) klicovaneho STABILNIM kodem
     * misto textu. Kanonicka data (kod/id) v DB se nemeni, jen zobrazeni. Chybejici
     * nebo prazdny preklad -> $fallback (cesky zdroj z DB).
     * Katalog: resources/lang/{domena}/{locale}.php  ('kod' => 'preklad').
     */
    public static function td(string $domain, string $key, string $fallback): string
    {
        if (self::$locale === self::DEFAULT) {
            return $fallback; // cestina je zdroj, katalog neni potreba
        }
        $cacheKey = $domain . '|' . self::$locale;
        if (!isset(self::$domains[$cacheKey])) {
            self::$domains[$cacheKey] = self::loadDomain($domain, self::$locale);
        }
        $value = self::$domains[$cacheKey][$key] ?? null;
        return (is_string($value) && $value !== '') ? $value : $fallback;
    }

    /** @return array<string, string> */
    private static function loadDomain(string $domain, string $locale): array
    {
        $file = self::root() . '/resources/lang/' . $domain . '/' . $locale . '.php';
        if (!is_file($file)) {
            return [];
        }
        $data = require $file;
        return is_array($data) ? $data : [];
    }

    /** Nalezeny preklad (neprazdny), jinak null. */
    private static function lookup(string $key): ?string
    {
        $catalog = self::catalog();
        $value = $catalog[$key] ?? null;
        return (is_string($value) && $value !== '') ? $value : null;
    }

    /** @return array<string, string> */
    private static function catalog(): array
    {
        if (self::$catalog === null) {
            self::$catalog = self::loadCatalog(self::$locale);
        }
        return self::$catalog;
    }

    /** @return array<string, string> */
    private static function loadCatalog(string $locale): array
    {
        if ($locale === self::DEFAULT) {
            return []; // cestina je zdroj, katalog neni potreba
        }
        $file = self::root() . '/resources/lang/' . $locale . '.php';
        if (!is_file($file)) {
            return [];
        }
        $data = require $file;
        return is_array($data) ? $data : [];
    }

    /** @param array<string, string|int|float> $params */
    private static function render(string $text, array $params): string
    {
        if ($params === []) {
            return $text;
        }
        $replace = [];
        foreach ($params as $key => $value) {
            $replace['{' . $key . '}'] = (string) $value;
        }
        return strtr($text, $replace);
    }

    private static function root(): string
    {
        return defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
    }

    /** Test seam: vycisti nactene katalogy/registr. */
    public static function flush(): void
    {
        self::$locale = self::DEFAULT;
        self::$catalog = null;
        self::$available = null;
        self::$domains = [];
    }
}
