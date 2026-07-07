<?php
declare(strict_types=1);

/**
 * Extrakce prekladovych klicu z t()/tc() volani do kostry katalogu.
 * Projde app/, najde t('...') a tc('...','...') (jen JEDNODUCHE uvozovky) a
 * aktualizuje resources/lang/en.php + es.php: doplni nove klice s prazdnou
 * hodnotou, zachova stavajici preklady (i pro klice, ktere uz v kodu nejsou).
 *
 * Bezi LOKALNE (na serveru neni CLI):  php bin/i18n_extract.php
 */

define('ROOT_PATH', dirname(__DIR__));

const CTX_SEP = "\x04"; // oddelovac kontext/text (jako gettext msgctxt)

$scanDirs = [ROOT_PATH . '/app'];
$locales = ['en', 'es'];

/** @return string PHP single-quote unescape (jen \' a \\). */
$unescape = static fn (string $s): string => str_replace(['\\\\', "\\'"], ['\\', "'"], $s);

// 1) Sesbirej klice ze zdrojaku.
$keys = []; // klic => true
foreach ($scanDirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $code = (string) file_get_contents($file->getPathname());

        // tc('kontext', 'text')
        if (preg_match_all('/(?<![\w>$])tc\s*\(\s*\'((?:\\\\.|[^\'\\\\])*)\'\s*,\s*\'((?:\\\\.|[^\'\\\\])*)\'/', $code, $m, PREG_SET_ORDER)) {
            foreach ($m as $hit) {
                $keys[$unescape($hit[1]) . CTX_SEP . $unescape($hit[2])] = true;
            }
        }
        // t('text')
        if (preg_match_all('/(?<![\w>$])t\s*\(\s*\'((?:\\\\.|[^\'\\\\])*)\'/', $code, $m, PREG_SET_ORDER)) {
            foreach ($m as $hit) {
                $keys[$unescape($hit[1])] = true;
            }
        }
    }
}
$found = array_keys($keys);
sort($found, SORT_STRING);

// 2) Pro kazdy jazyk sluc s existujicim katalogem a zapis.
foreach ($locales as $loc) {
    $file = ROOT_PATH . '/resources/lang/' . $loc . '.php';
    $existing = is_file($file) ? require $file : [];
    $existing = is_array($existing) ? $existing : [];

    $merged = [];
    foreach ($found as $k) {
        $merged[$k] = isset($existing[$k]) && is_string($existing[$k]) ? $existing[$k] : '';
    }
    // Zachovej i preklady klicu, ktere uz v kodu nejsou (neztracet praci).
    foreach ($existing as $k => $v) {
        if (!array_key_exists($k, $merged) && is_string($v)) {
            $merged[$k] = $v;
        }
    }
    ksort($merged, SORT_STRING);

    $out = "<?php\ndeclare(strict_types=1);\n\n"
        . "// Katalog jazyka '{$loc}': 'cesky zdroj' => 'preklad'. Prazdna hodnota = fallback\n"
        . "// na cestinu. Kontextove klice obsahuji oddelovac \\x04 (kontext\\x04text).\n"
        . "// Generovano bin/i18n_extract.php - hodnoty doplnujte, klice nechte.\n"
        . "return [\n";
    foreach ($merged as $k => $v) {
        $out .= '    ' . var_export($k, true) . ' => ' . var_export($v, true) . ",\n";
    }
    $out .= "];\n";

    file_put_contents($file, $out);
    echo "  {$loc}.php: " . count($merged) . " klicu\n";
}

echo 'Hotovo. Nalezeno klicu v kodu: ' . count($found) . "\n";
