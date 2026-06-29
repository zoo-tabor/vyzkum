<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;
use App\Core\Session;

require __DIR__ . '/autoload.php';

define('ROOT_PATH', dirname(__DIR__));
define('STORAGE_PATH', ROOT_PATH . '/storage');

foreach (['logs', 'uploads', 'exports'] as $dir) {
    $path = STORAGE_PATH . '/' . $dir;
    if (!is_dir($path)) {
        @mkdir($path, 0775, true);
    }
}

$config = Config::load(ROOT_PATH . '/.env');
$debug = (bool) $config->get('APP_DEBUG', false);
define('APP_DEBUG', $debug);
define('ASSET_BASE_PATH', (string) $config->get('ASSET_BASE_PATH', ''));

error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');
ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', $debug ? '1' : '0');

// Sessions only in a web context (CLI tooling does not need them).
if (PHP_SAPI !== 'cli') {
    Session::start();
}

Database::configure($config);

return $config;
