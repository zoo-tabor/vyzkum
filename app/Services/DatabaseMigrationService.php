<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class DatabaseMigrationService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /** @return array<int, string> */
    public function tables(): array
    {
        $tables = [];
        foreach ($this->pdo->query('SHOW TABLES') as $row) {
            $tables[] = (string) reset($row);
        }

        return $tables;
    }

    public function vetChamberNumberExists(): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'vets'
              AND COLUMN_NAME = 'chamber_number'
        ");
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    public function dropVetChamberNumber(): void
    {
        if (!$this->vetChamberNumberExists()) {
            return;
        }

        $this->pdo->exec('ALTER TABLE vets DROP COLUMN chamber_number');
    }

    /** @return array<int, string> */
    public function missingBatchStorageObjects(): array
    {
        if (!$this->tableExists('samples')) {
            return [];
        }

        $missing = [];
        if (!$this->tableExists('sample_batches')) {
            $missing[] = 'sample_batches';
        }

        foreach (['batch_id', 'batch_sequence', 'vet_token', 'owner_token'] as $column) {
            if (!$this->columnExists('samples', $column)) {
                $missing[] = 'samples.' . $column;
            }
        }

        return $missing;
    }

    public function installBatchStorage(): void
    {
        if (!$this->tableExists('sample_batches')) {
            $this->pdo->exec("
                CREATE TABLE sample_batches (
                  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                  vet_id INT UNSIGNED NULL,
                  label VARCHAR(160) NULL,
                  sample_count INT UNSIGNED NOT NULL DEFAULT 0,
                  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  CONSTRAINT sample_batches_vet_fk FOREIGN KEY (vet_id) REFERENCES vets(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        if (!$this->columnExists('samples', 'batch_id')) {
            $this->pdo->exec('ALTER TABLE samples ADD COLUMN batch_id INT UNSIGNED NULL AFTER sample_id');
            $this->pdo->exec('ALTER TABLE samples ADD CONSTRAINT samples_batch_fk FOREIGN KEY (batch_id) REFERENCES sample_batches(id) ON DELETE SET NULL');
        }

        if (!$this->columnExists('samples', 'batch_sequence')) {
            $this->pdo->exec('ALTER TABLE samples ADD COLUMN batch_sequence INT UNSIGNED NULL AFTER batch_id');
        }

        if (!$this->columnExists('samples', 'vet_token')) {
            $this->pdo->exec('ALTER TABLE samples ADD COLUMN vet_token VARCHAR(128) NULL AFTER owner_token_hash');
        }

        if (!$this->columnExists('samples', 'owner_token')) {
            $this->pdo->exec('ALTER TABLE samples ADD COLUMN owner_token VARCHAR(128) NULL AFTER vet_token');
        }
    }

    /** @return array<int, string> */
    public function migrate(string $schemaPath): array
    {
        $existingTables = $this->tables();
        if ($existingTables !== []) {
            throw new \RuntimeException('Databaze uz obsahuje tabulky: ' . implode(', ', $existingTables));
        }

        $schema = file_get_contents($schemaPath);
        if ($schema === false || trim($schema) === '') {
            throw new \RuntimeException('SQL schema nebylo nalezeno nebo je prazdne.');
        }

        $statements = array_filter(array_map('trim', explode(';', $schema)));
        foreach ($statements as $statement) {
            $this->pdo->exec($statement);
        }

        return $this->tables();
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
        ");
        $stmt->execute(['table_name' => $table]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table_name
              AND COLUMN_NAME = :column_name
        ");
        $stmt->execute(['table_name' => $table, 'column_name' => $column]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
