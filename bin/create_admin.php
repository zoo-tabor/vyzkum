<?php
declare(strict_types=1);

// Seed / reset the central research_admin account.
// Usage: php bin/create_admin.php <email> <password> [role]

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Repositories\UserRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Support\Policy;

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;
$role = $argv[3] ?? 'research_admin';

if ($email === null || $password === null) {
    fwrite(STDERR, "Pouziti: php bin/create_admin.php <email> <password> [role]\n");
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Neplatny e-mail.\n");
    exit(1);
}

if (strlen($password) < 10) {
    fwrite(STDERR, "Heslo musi mit aspon 10 znaku.\n");
    exit(1);
}

if (!in_array($role, Policy::ROLES, true)) {
    fwrite(STDERR, 'Neplatna role. Povolene: ' . implode(', ', Policy::ROLES) . "\n");
    exit(1);
}

try {
    $id = (new UserRepository())->upsert(strtolower(trim($email)), Auth::hash($password), $role);
    AuditService::log(null, 'cli', 'user_seeded', 'user', (string) $id, null, ['email' => $email, 'role' => $role]);
    echo "Ucet pripraven: #{$id} {$email} ({$role}).\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Chyba: ' . $e->getMessage() . "\n");
    exit(1);
}
