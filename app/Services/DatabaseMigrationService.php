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
}
