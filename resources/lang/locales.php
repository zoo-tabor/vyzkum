<?php
declare(strict_types=1);

// Registr dostupnych jazyku: kod => ['name' => nazev v ORIGINALNIM jazyce,
// 'flag' => kod vlajky v public/assets/flags/<flag>.svg].
// Pridani jazyka = pridat radek + soubor resources/lang/<kod>.php.
// Cestina je zdrojovy jazyk (klice katalogu jsou ceske texty), proto nema soubor.
return [
    'cs' => ['name' => 'Čeština', 'flag' => 'cz'],
    'de' => ['name' => 'Deutsch', 'flag' => 'de'],
    'en' => ['name' => 'English', 'flag' => 'gb'],
    'es' => ['name' => 'Español', 'flag' => 'es'],
    'fr' => ['name' => 'Français', 'flag' => 'fr'],
    'it' => ['name' => 'Italiano', 'flag' => 'it'],
    'hu' => ['name' => 'Magyar', 'flag' => 'hu'],
    'pl' => ['name' => 'Polski', 'flag' => 'pl'],
    'ru' => ['name' => 'Русский', 'flag' => 'ru'],
];
