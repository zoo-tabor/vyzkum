<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class BreedRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    /** @return array<int, array<string, mixed>> */
    public function all(bool $onlyActive = true): array
    {
        $sql = 'SELECT id, slug, name, club_id, is_active FROM breeds';
        if ($onlyActive) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY name ASC';
        return $this->pdo()->query($sql)->fetchAll();
    }

    /**
     * Breeds a user may access. research_admin gets everything; others only
     * those granted in user_breed_access.
     *
     * @return array<int, array<string, mixed>>
     */
    public function accessibleFor(int $userId, string $role): array
    {
        if ($role === 'research_admin') {
            return $this->all();
        }

        $stmt = $this->pdo()->prepare(
            'SELECT b.id, b.slug, b.name, b.club_id, b.is_active
             FROM breeds b
             INNER JOIN user_breed_access uba ON uba.breed_id = b.id
             WHERE uba.user_id = :uid AND b.is_active = 1
             ORDER BY b.name ASC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM breeds WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function existsSlug(string $slug): bool
    {
        $stmt = $this->pdo()->prepare('SELECT 1 FROM breeds WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return (bool) $stmt->fetchColumn();
    }

    public function create(string $slug, string $name, ?int $clubId = null): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO breeds (slug, name, club_id, is_active) VALUES (:slug, :name, :club, 1)'
        );
        $stmt->execute(['slug' => $slug, 'name' => $name, 'club' => $clubId]);
        return (int) $this->pdo()->lastInsertId();
    }

    public function count(): int
    {
        return (int) $this->pdo()->query('SELECT COUNT(*) FROM breeds')->fetchColumn();
    }
}
