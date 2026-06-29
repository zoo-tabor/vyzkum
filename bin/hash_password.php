<?php
declare(strict_types=1);

// Vygeneruje hash hesla (stejny algoritmus jako aplikace - Argon2id).
// Pouziti: php bin/hash_password.php "vase-heslo"

require dirname(__DIR__) . '/app/autoload.php';

use App\Services\Auth;

$password = $argv[1] ?? null;
if ($password === null || $password === '') {
    fwrite(STDERR, "Pouziti: php bin/hash_password.php \"vase-heslo\"\n");
    exit(1);
}

echo Auth::hash($password) . PHP_EOL;
