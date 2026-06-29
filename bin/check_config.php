<?php
declare(strict_types=1);

// Kontrola konfigurace a pripravenosti pro provoz.
// Pouziti: php bin/check_config.php

$config = require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Migrator;

$fail = 0;
$line = static function (string $level, string $msg) use (&$fail): void {
    if ($level === 'FAIL') {
        $fail++;
    }
    echo str_pad("[{$level}]", 8) . $msg . "\n";
};

// APP_KEY
$key = (string) $config->get('APP_KEY', '');
$line(strlen($key) >= 32 ? 'OK' : 'FAIL', 'APP_KEY delka ' . strlen($key) . ' znaku (min 32)');

// APP_DEBUG
$debug = (bool) $config->get('APP_DEBUG', false);
$line($debug ? 'WARN' : 'OK', 'APP_DEBUG = ' . ($debug ? 'true (na produkci doporuceno false)' : 'false'));

// APP_URL https
$url = (string) $config->get('APP_URL', '');
$line(str_starts_with($url, 'https://') ? 'OK' : 'WARN', 'APP_URL = ' . $url . (str_starts_with($url, 'https://') ? '' : ' (odkazy v e-mailech budou bez HTTPS)'));

// Mail
$line('OK', 'MAIL_ENABLED = ' . ($config->get('MAIL_ENABLED', false) ? 'true' : 'false') . ', transport = ' . (string) $config->get('MAIL_TRANSPORT', 'mail'));

// 2FA
$line('OK', 'ENFORCE_ADMIN_2FA = ' . ($config->get('ENFORCE_ADMIN_2FA', true) ? 'true' : 'false'));

// DB connect
try {
    Database::pdo()->query('SELECT 1');
    $line('OK', 'Pripojeni k databazi (' . $config->get('DB_DATABASE', '') . ')');

    $migrator = new Migrator(Database::pdo(), ROOT_PATH . '/database/migrations');
    $pending = $migrator->pending();
    $line($pending === [] ? 'OK' : 'WARN', 'Migrace: ' . ($pending === [] ? 'vse aplikovano' : 'nespustene: ' . implode(', ', $pending)));
} catch (Throwable $e) {
    $line('FAIL', 'Databaze: ' . $e->getMessage());
}

// Storage
foreach (['logs', 'uploads', 'exports'] as $dir) {
    $path = STORAGE_PATH . '/' . $dir;
    $line(is_dir($path) && is_writable($path) ? 'OK' : 'FAIL', "storage/{$dir} zapisovatelny");
}

echo "\n" . ($fail === 0 ? 'Vse v poradku.' : "Nalezeno {$fail} problemu (FAIL).") . "\n";
exit($fail > 0 ? 1 : 0);
