<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class VetRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo()->query('SELECT * FROM vets ORDER BY name ASC')->fetchAll();
    }

    public function create(string $name, ?string $clinic, ?string $email, ?string $phone): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO vets (name, clinic_name, email, phone) VALUES (:n, :c, :e, :p)'
        );
        $stmt->execute(['n' => $name, 'c' => $clinic, 'e' => $email, 'p' => $phone]);
        return (int) $this->pdo()->lastInsertId();
    }
}
