<?php
declare(strict_types=1);

/**
 * Generuje kostry prekladu statickych enum ciselniku (domenove katalogy klicovane
 * STABILNIM kodem). Zdroj pravdy = konstanty v Support tridach (bez duplicity).
 * Pro kazdy jazyk z registru (krome cs) zapise resources/lang/{domena}/{loc}.php,
 * doplni nove kody s prazdnou hodnotou a ZACHOVA jiz vyplnene preklady.
 *
 * Bezi LOKALNE:  php bin/i18n_enums.php
 */

define('ROOT_PATH', dirname(__DIR__));
require ROOT_PATH . '/app/autoload.php';

use App\Support\FormSchema;
use App\Support\Gwas;
use App\Support\HealthEventType;
use App\Support\SampleStatus;

// domena => mapa 'kod' => 'cesky zdroj'
$domains = [
    'form_types' => FormSchema::TYPES,
    'health_event_types' => HealthEventType::LABELS,
    'sample_status' => SampleStatus::LABELS,
    'gwas' => Gwas::LABELS,
];

$registry = require ROOT_PATH . '/resources/lang/locales.php';
$locales = array_values(array_filter(array_keys($registry), static fn (string $c): bool => $c !== 'cs'));

foreach ($domains as $domain => $map) {
    $dir = ROOT_PATH . '/resources/lang/' . $domain;
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    foreach ($locales as $loc) {
        $file = $dir . '/' . $loc . '.php';
        $existing = is_file($file) ? require $file : [];
        $existing = is_array($existing) ? $existing : [];

        $out = "<?php\ndeclare(strict_types=1);\n\n"
            . "// Preklad ciselniku '{$domain}' pro jazyk '{$loc}': 'kod' => 'preklad'.\n"
            . "// Prazdna hodnota = fallback na cesky zdroj (uvedeny v komentari za klicem).\n"
            . "// Generovano bin/i18n_enums.php - hodnoty doplnujte, kody nechte.\n"
            . "return [\n";
        foreach ($map as $code => $cs) {
            $value = isset($existing[$code]) && is_string($existing[$code]) ? $existing[$code] : '';
            $pad = str_pad(var_export((string) $code, true), 18);
            $out .= '    ' . $pad . ' => ' . var_export($value, true) . ', // ' . $cs . "\n";
        }
        $out .= "];\n";

        file_put_contents($file, $out);
        echo "  {$domain}/{$loc}.php: " . count($map) . " kodu\n";
    }
}

echo "Hotovo. Domen: " . count($domains) . "\n";
