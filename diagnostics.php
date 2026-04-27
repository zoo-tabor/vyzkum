<?php
declare(strict_types=1);

$config = require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/helpers.php';

if (!$config->get('APP_DEBUG', false)) {
    http_response_code(404);
    exit('Not found');
}

header('Content-Type: text/plain; charset=utf-8');

echo "Dog research diagnostics\n";
echo "========================\n";
echo 'PHP: ' . PHP_VERSION . "\n";
echo 'SAPI: ' . PHP_SAPI . "\n";
echo 'ROOT_PATH: ' . ROOT_PATH . "\n";
echo 'APP_ENV: ' . (string) $config->get('APP_ENV', '') . "\n";
echo 'APP_URL: ' . (string) $config->get('APP_URL', '') . "\n";
echo 'DB_HOST: ' . (string) $config->get('DB_HOST', '') . "\n";
echo 'pdo_mysql: ' . (extension_loaded('pdo_mysql') ? 'yes' : 'no') . "\n";
echo "\nFiles\n";
echo '.env: ' . (is_file(ROOT_PATH . '/.env') ? 'yes' : 'no') . "\n";
echo 'public/assets/app.css: ' . (is_file(ROOT_PATH . '/public/assets/app.css') ? 'yes' : 'no') . "\n";
echo 'database/schema.sql: ' . (is_file(ROOT_PATH . '/database/schema.sql') ? 'yes' : 'no') . "\n";
echo 'storage/logs writable: ' . (is_writable(ROOT_PATH . '/storage/logs') ? 'yes' : 'no') . "\n";
echo 'storage/uploads writable: ' . (is_writable(ROOT_PATH . '/storage/uploads') ? 'yes' : 'no') . "\n";

echo "\nDatabase\n";
try {
    $pdo = App\Core\Database::pdo();
    echo 'connection: ok' . "\n";
    $tables = [];
    foreach ($pdo->query('SHOW TABLES') as $row) {
        $tables[] = (string) reset($row);
    }
    echo 'tables: ' . ($tables ? implode(', ', $tables) : '(none)') . "\n";
} catch (Throwable $e) {
    echo 'connection: failed' . "\n";
    echo 'error: ' . $e->getMessage() . "\n";
}
