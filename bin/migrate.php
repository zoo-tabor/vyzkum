<?php
declare(strict_types=1);

// Usage:
//   php bin/migrate.php status
//   php bin/migrate.php up

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\Migrator;

$command = $argv[1] ?? 'status';
$migrator = new Migrator(Database::pdo(), ROOT_PATH . '/database/migrations');

try {
    switch ($command) {
        case 'status':
            $applied = $migrator->applied();
            echo "Migrace:\n";
            foreach ($migrator->all() as $file) {
                $mark = in_array($file, $applied, true) ? '[x]' : '[ ]';
                echo "  {$mark} {$file}\n";
            }
            break;

        case 'up':
            $done = $migrator->run();
            if ($done === []) {
                echo "Zadne nove migrace. Vse je aktualni.\n";
            } else {
                echo "Aplikovano:\n";
                foreach ($done as $file) {
                    echo "  + {$file}\n";
                }
            }
            break;

        default:
            fwrite(STDERR, "Neznamy prikaz: {$command}. Pouzijte 'status' nebo 'up'.\n");
            exit(1);
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Chyba migrace: ' . $e->getMessage() . "\n");
    exit(1);
}
