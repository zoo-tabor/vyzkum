<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class ColourRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    /** @return array<int, array<string, mixed>> */
    public function forBreed(int $breedId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM colours WHERE breed_id = :b ORDER BY position ASC, name ASC');
        $stmt->execute(['b' => $breedId]);
        return $stmt->fetchAll();
    }

    /** @return array<int, string> jen názvy barev plemene (pro výběr) */
    public function namesForBreed(int $breedId): array
    {
        return array_map(static fn (array $c): string => (string) $c['name'], $this->forBreed($breedId));
    }

    /** @return array<int, array<int, string>> breed_id => [názvy barev] (pro JS výběr) */
    public function allGrouped(): array
    {
        $rows = $this->pdo()->query('SELECT breed_id, name FROM colours ORDER BY breed_id ASC, position ASC, name ASC')->fetchAll();
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['breed_id']][] = (string) $r['name'];
        }
        return $map;
    }

    public function create(int $breedId, string $name): int
    {
        $stmt = $this->pdo()->prepare('INSERT INTO colours (breed_id, name) VALUES (:b, :n)');
        $stmt->execute(['b' => $breedId, 'n' => $name]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM colours WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM colours WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
