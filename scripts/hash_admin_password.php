<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

$password = $argv[1] ?? '';
if ($password === '') {
    exit("Pouziti: php scripts/hash_admin_password.php \"heslo\"\n");
}

echo password_hash($password, PASSWORD_DEFAULT) . PHP_EOL;
