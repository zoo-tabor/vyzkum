<?php
declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;
use App\Core\Session;

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

define('ROOT_PATH', dirname(__DIR__));
define('STORAGE_PATH', ROOT_PATH . '/storage');

$config = Config::load(ROOT_PATH . '/.env');
$debug = (bool) $config->get('APP_DEBUG', false);
define('ASSET_BASE_PATH', (string) $config->get('ASSET_BASE_PATH', ''));

error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');
ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', $debug ? '1' : '0');

Session::start((bool) $config->get('APP_DEBUG', false));
Database::configure($config);

return $config;
