<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Minimal SQL migration runner. Each migration is a numbered .sql file in the
 * migrations directory; applied files are recorded in `schema_migrations`.
 *
 * Note: MySQL/MariaDB DDL is auto-committing, so a migration is not rolled back
 * mid-file. Keep one logical change per file and the files small.
 */
final class Migrator
{
    public function __construct(private PDO $pdo, private string $dir)
    {
    }

    public function ensureTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(190) NOT NULL PRIMARY KEY,
                executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /** @return array<int, string> migration filenames, sorted */
    public function all(): array
    {
        $files = glob($this->dir . '/*.sql') ?: [];
        $names = array_map('basename', $files);
        sort($names, SORT_STRING);
        return $names;
    }

    /** @return array<int, string> already-applied versions */
    public function applied(): array
    {
        $this->ensureTable();
        $rows = $this->pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
        return array_map('strval', $rows);
    }

    /** @return array<int, string> pending migration filenames */
    public function pending(): array
    {
        $applied = $this->applied();
        return array_values(array_diff($this->all(), $applied));
    }

    /**
     * Run all pending migrations.
     *
     * @return array<int, string> the versions that were applied
     */
    public function run(): array
    {
        $done = [];
        foreach ($this->pending() as $file) {
            $this->execFile($this->dir . '/' . $file);
            $stmt = $this->pdo->prepare('INSERT INTO schema_migrations (version) VALUES (:v)');
            $stmt->execute(['v' => $file]);
            $done[] = $file;
        }
        return $done;
    }

    private function execFile(string $path): void
    {
        $sql = (string) file_get_contents($path);

        foreach ($this->splitStatements($sql) as $statement) {
            $this->pdo->exec($statement);
        }
    }

    /** @return array<int, string> */
    private function splitStatements(string $sql): array
    {
        // Strip line comments (-- ...) then split on semicolons. Adequate for
        // plain DDL; revisit if stored routines/triggers are introduced.
        $lines = [];
        foreach (preg_split('/\r?\n/', $sql) ?: [] as $line) {
            $trimmed = ltrim($line);
            if (str_starts_with($trimmed, '--')) {
                continue;
            }
            $lines[] = $line;
        }

        $clean = implode("\n", $lines);
        $parts = array_map('trim', explode(';', $clean));

        return array_values(array_filter($parts, static fn (string $s): bool => $s !== ''));
    }
}
