<?php
declare(strict_types=1);

/**
 * Generuje kostru prekladu ciselniku pricin umrti (domenovy katalog klicovany
 * STABILNIM kodem, ne textem - zmena ceskeho labelu nerozbije preklady).
 *
 * Zdroj pravdy = pole $causes nize (kod => cesky label), musi odpovidat seedu v
 * database/migrations/016_death_causes.sql. Pro kazdy jazyk z registru (krome cs)
 * zapise resources/lang/death_causes/{loc}.php ('kod' => 'preklad'), doplni nove
 * kody s prazdnou hodnotou a ZACHOVA jiz vyplnene preklady.
 *
 * Bezi LOKALNE:  php bin/i18n_death_causes.php
 */

define('ROOT_PATH', dirname(__DIR__));

// Kanonicky seznam pricin umrti (kod => cesky zdroj). Drz v souladu se seedem 016.
$causes = [
    '1'      => 'Nemoc',
    '1.1'    => 'Endokrinní onemocnění',
    '1.1.1'  => 'Cukrovka',
    '1.1.2'  => 'Cushingův syndrom',
    '1.1.3'  => 'Hypotyreóza',
    '1.1.4'  => 'Jiné endokrinní onemocnění',
    '1.2'    => 'Imunologické onemocnění',
    '1.2.1'  => 'Imunitně zprostředkovaná hemolytická anémie (IMHA/AIHA)',
    '1.2.2'  => 'Trombocytopenie',
    '1.2.3'  => 'Jiné imunologické onemocnění',
    '1.3'    => 'Kožní onemocnění',
    '1.3.1'  => 'Jiné kožní onemocnění',
    '1.4'    => 'Nádorová onemocnění',
    '1.4.1'  => 'Lymfom',
    '1.4.2'  => 'Nádor jater, ledvin nebo střevního traktu',
    '1.4.3'  => 'Nádor kostí nebo kloubů',
    '1.4.4'  => 'Nádor kůže nebo podkoží',
    '1.4.5'  => 'Nádor mléčné žlázy',
    '1.4.6'  => 'Nádor močového měchýře',
    '1.4.7'  => 'Nádor nervové soustavy',
    '1.4.8'  => 'Nádor plic',
    '1.4.9'  => 'Nádor sleziny, srdce nebo cévního systému',
    '1.4.10' => 'Jiné nádorové onemocnění',
    '1.5'    => 'Neurologické onemocnění',
    '1.5.1'  => 'Epilepsie',
    '1.5.2'  => 'Syringomyelie',
    '1.5.3'  => 'Jiné neurologické onemocnění',
    '1.6'    => 'Oční onemocnění',
    '1.6.1'  => 'Slepota',
    '1.6.2'  => 'Syndrom suchého oka',
    '1.6.3'  => 'Jiné oční onemocnění',
    '1.7'    => 'Onemocnění pohybového aparátu',
    '1.7.1'  => 'Artróza jiného kloubu než kyčelního nebo loketního',
    '1.7.2'  => 'Deformující spondylóza',
    '1.7.3'  => 'Dysplazie kyčelního kloubu a následná artróza',
    '1.7.4'  => 'Dysplazie loketního kloubu a následná artróza',
    '1.7.5'  => 'Imunitně zprostředkovaná polyartritida',
    '1.7.6'  => 'Jiná dysplazie kostí nebo kloubů',
    '1.7.7'  => 'Luxace čéšky',
    '1.7.8'  => 'Poranění předního zkříženého vazu',
    '1.7.9'  => 'Syndrom kaudy equiny',
    '1.7.10' => 'Výhřez meziobratlové ploténky',
    '1.7.11' => 'Jiné onemocnění pohybového aparátu',
    '1.8'    => 'Onemocnění trávicí soustavy',
    '1.8.1'  => 'Exokrinní pankreatická insuficience (EPI)',
    '1.8.2'  => 'Jaterní insuficience / selhání jater',
    '1.8.3'  => 'Megaezofagus',
    '1.8.4'  => 'Neprůchodnost střeva způsobená cizím tělesem',
    '1.8.5'  => 'Jiné onemocnění trávicí soustavy',
    '1.9'    => 'Respirační onemocnění',
    '1.9.1'  => 'Kolaps průdušnice',
    '1.9.2'  => 'Pneumonie',
    '1.9.3'  => 'Jiné respirační onemocnění',
    '1.10'   => 'Srdeční onemocnění',
    '1.10.1' => 'Endokardióza',
    '1.10.2' => 'Kardiomyopatie',
    '1.10.3' => 'Jiné srdeční onemocnění',
    '1.11'   => 'Urologická onemocnění',
    '1.11.1' => 'Infekce dělohy / pyometra',
    '1.11.2' => 'Ledvinové kameny',
    '1.11.3' => 'Močová inkontinence',
    '1.11.4' => 'Selhání ledvin',
    '1.11.5' => 'Jiné urologické onemocnění',
    '1.12'   => 'Ušní onemocnění',
    '1.12.1' => 'Chronický nebo opakovaný zánět ucha',
    '1.12.2' => 'Jiné ušní onemocnění',
    '1.13'   => 'Vrozená vada',
    '1.13.1' => 'Jiná vývojová porucha',
    '1.13.2' => 'Vrozená anomálie obratlů',
    '1.13.3' => 'Vrozená vada nebo malformace štěněte',
    '1.13.4' => 'Vrozená vývojová vada srdce',
    '1.13.5' => 'Jiné vrozené onemocnění',
    '1.14'   => 'Jiné nespecifikované onemocnění',
    '2'      => 'Stáří',
    '3'      => 'Nehoda',
    '4'      => 'Jiné',
];

$registry = require ROOT_PATH . '/resources/lang/locales.php';
$locales = array_values(array_filter(array_keys($registry), static fn (string $c): bool => $c !== 'cs'));

$dir = ROOT_PATH . '/resources/lang/death_causes';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

foreach ($locales as $loc) {
    $file = $dir . '/' . $loc . '.php';
    $existing = is_file($file) ? require $file : [];
    $existing = is_array($existing) ? $existing : [];

    $out = "<?php\ndeclare(strict_types=1);\n\n"
        . "// Preklad ciselniku pricin umrti pro jazyk '{$loc}': 'kod' => 'preklad'.\n"
        . "// Prazdna hodnota = fallback na cesky zdroj (uvedeny v komentari za klicem).\n"
        . "// Klicem je STABILNI kod, takze uprava ceskeho textu nerozbije preklady.\n"
        . "// Generovano bin/i18n_death_causes.php - hodnoty doplnujte, kody nechte.\n"
        . "return [\n";
    foreach ($causes as $code => $cs) {
        $value = isset($existing[$code]) && is_string($existing[$code]) ? $existing[$code] : '';
        $pad = str_pad(var_export((string) $code, true), 10);
        $out .= '    ' . $pad . ' => ' . var_export($value, true) . ', // ' . $cs . "\n";
    }
    $out .= "];\n";

    file_put_contents($file, $out);
    echo "  death_causes/{$loc}.php: " . count($causes) . " kodu\n";
}

echo 'Hotovo. Kodu pricin umrti: ' . count($causes) . "\n";
