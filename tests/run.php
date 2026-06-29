<?php
declare(strict_types=1);

// Lightweight test runner (no Composer/PHPUnit required).
// Usage: php tests/run.php

require dirname(__DIR__) . '/app/autoload.php';

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
if (!defined('ASSET_BASE_PATH')) {
    define('ASSET_BASE_PATH', '');
}

$GLOBALS['__tests'] = ['pass' => 0, 'fail' => 0];

function test(string $name, callable $fn): void
{
    try {
        $fn();
        $GLOBALS['__tests']['pass']++;
        echo "  PASS  {$name}\n";
    } catch (Throwable $e) {
        $GLOBALS['__tests']['fail']++;
        echo "  FAIL  {$name} -> " . $e->getMessage() . "\n";
    }
}

function assert_true(mixed $cond, string $msg = 'ocekavano true'): void
{
    if ($cond !== true) {
        throw new RuntimeException($msg);
    }
}

function assert_false(mixed $cond, string $msg = 'ocekavano false'): void
{
    if ($cond !== false) {
        throw new RuntimeException($msg);
    }
}

function assert_same(mixed $expected, mixed $actual, string $msg = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            ($msg !== '' ? $msg . ' | ' : '') .
            'ocekavano ' . var_export($expected, true) . ', ziskano ' . var_export($actual, true)
        );
    }
}

foreach (glob(__DIR__ . '/Unit/*.php') ?: [] as $file) {
    echo "\n" . basename($file) . "\n";
    require $file;
}

$t = $GLOBALS['__tests'];
echo "\n" . str_repeat('-', 44) . "\n";
echo "PASS: {$t['pass']}   FAIL: {$t['fail']}\n";
exit($t['fail'] > 0 ? 1 : 0);
