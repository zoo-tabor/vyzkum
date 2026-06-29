<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class DogRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    /**
     * One query for the page: dog rows + breed + current owner + primary email.
     * No N+1.
     *
     * @param array<string, mixed> $params bound params from DogQuery::filters()
     * @return array<int, array<string, mixed>>
     */
    public function paginate(string $where, array $params, string $orderBy, int $limit, int $offset): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        $stmt = $this->pdo()->prepare(
            "SELECT d.id, d.name, d.chip_number, d.pedigree_number, d.sex, d.birth_date, d.death_date, d.status,
                    b.name AS breed_name,
                    o.id AS owner_id, o.display_name AS owner_name,
                    (SELECT email FROM owner_emails e WHERE e.owner_id = o.id AND e.is_primary = 1 LIMIT 1) AS owner_email
             FROM dogs d
             JOIN breeds b ON b.id = d.breed_id
             LEFT JOIN dog_owners do2 ON do2.dog_id = d.id AND do2.is_current = 1
             LEFT JOIN owners o ON o.id = do2.owner_id
             WHERE {$where}
             ORDER BY {$orderBy}
             LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $params */
    public function count(string $where, array $params): int
    {
        $stmt = $this->pdo()->prepare("SELECT COUNT(*) FROM dogs d WHERE {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT d.*, b.name AS breed_name, b.slug AS breed_slug
             FROM dogs d JOIN breeds b ON b.id = d.breed_id
             WHERE d.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function currentOwner(int $dogId): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT o.* FROM dog_owners do2
             JOIN owners o ON o.id = do2.owner_id
             WHERE do2.dog_id = :id AND do2.is_current = 1 LIMIT 1'
        );
        $stmt->execute(['id' => $dogId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function ownersHistory(int $dogId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT o.id, o.display_name, do2.is_current, do2.relationship_type,
                    do2.valid_from, do2.valid_to, do2.source, do2.created_at
             FROM dog_owners do2
             JOIN owners o ON o.id = do2.owner_id
             WHERE do2.dog_id = :id
             ORDER BY do2.is_current DESC, do2.created_at DESC'
        );
        $stmt->execute(['id' => $dogId]);
        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $d */
    public function create(array $d): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO dogs
                (breed_id, name, kennel_name, chip_number, pedigree_number, sex,
                 birth_date, color, test_group, health_summary, status)
             VALUES
                (:breed_id, :name, :kennel_name, :chip_number, :pedigree_number, :sex,
                 :birth_date, :color, :test_group, :health_summary, :status)'
        );
        $stmt->execute([
            'breed_id' => $d['breed_id'],
            'name' => $d['name'],
            'kennel_name' => self::nv($d['kennel_name'] ?? null),
            'chip_number' => self::nv($d['chip_number'] ?? null),
            'pedigree_number' => self::nv($d['pedigree_number'] ?? null),
            'sex' => in_array($d['sex'] ?? '', ['male', 'female', 'unknown'], true) ? $d['sex'] : 'unknown',
            'birth_date' => self::nv($d['birth_date'] ?? null),
            'color' => self::nv($d['color'] ?? null),
            'test_group' => self::nv($d['test_group'] ?? null),
            'health_summary' => self::nv($d['health_summary'] ?? null),
            'status' => $d['status'] ?? 'active',
        ]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $d */
    public function update(int $id, array $d): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE dogs SET
                breed_id = :breed_id, name = :name, kennel_name = :kennel_name,
                chip_number = :chip_number, pedigree_number = :pedigree_number, sex = :sex,
                birth_date = :birth_date, death_date = :death_date, death_cause = :death_cause,
                color = :color, test_group = :test_group, health_summary = :health_summary,
                updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'breed_id' => $d['breed_id'],
            'name' => $d['name'],
            'kennel_name' => self::nv($d['kennel_name'] ?? null),
            'chip_number' => self::nv($d['chip_number'] ?? null),
            'pedigree_number' => self::nv($d['pedigree_number'] ?? null),
            'sex' => in_array($d['sex'] ?? '', ['male', 'female', 'unknown'], true) ? $d['sex'] : 'unknown',
            'birth_date' => self::nv($d['birth_date'] ?? null),
            'death_date' => self::nv($d['death_date'] ?? null),
            'death_cause' => self::nv($d['death_cause'] ?? null),
            'color' => self::nv($d['color'] ?? null),
            'test_group' => self::nv($d['test_group'] ?? null),
            'health_summary' => self::nv($d['health_summary'] ?? null),
        ]);
    }

    /** Set the current owner: close previous current relation, open a new one. */
    public function setCurrentOwner(int $dogId, int $ownerId, string $source = 'admin'): void
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            $close = $pdo->prepare(
                'UPDATE dog_owners SET is_current = 0, valid_to = CURDATE()
                 WHERE dog_id = :d AND is_current = 1'
            );
            $close->execute(['d' => $dogId]);

            $open = $pdo->prepare(
                'INSERT INTO dog_owners (dog_id, owner_id, is_current, valid_from, source)
                 VALUES (:d, :o, 1, CURDATE(), :s)'
            );
            $open->execute(['d' => $dogId, 'o' => $ownerId, 's' => $source]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private static function nv(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
