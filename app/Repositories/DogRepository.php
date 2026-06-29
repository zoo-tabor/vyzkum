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
                 birth_date, death_date, death_cause, color, test_group, health_summary,
                 sample_received_at, status)
             VALUES
                (:breed_id, :name, :kennel_name, :chip_number, :pedigree_number, :sex,
                 :birth_date, :death_date, :death_cause, :color, :test_group, :health_summary,
                 :sample_received_at, :status)'
        );
        $stmt->execute([
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
            'sample_received_at' => self::nv($d['sample_received_at'] ?? null),
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

    public function chipExists(string $chip): bool
    {
        $stmt = $this->pdo()->prepare('SELECT 1 FROM dogs WHERE chip_number = :c LIMIT 1');
        $stmt->execute(['c' => $chip]);
        return (bool) $stmt->fetchColumn();
    }

    public function pedigreeExists(string $pedigree): bool
    {
        $stmt = $this->pdo()->prepare('SELECT 1 FROM dogs WHERE pedigree_number = :p LIMIT 1');
        $stmt->execute(['p' => $pedigree]);
        return (bool) $stmt->fetchColumn();
    }

    /** Link an owner to a brand-new dog (no previous relation to close). No own transaction. */
    public function linkOwner(int $dogId, int $ownerId, string $source = 'import'): void
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO dog_owners (dog_id, owner_id, is_current, valid_from, source)
             VALUES (:d, :o, 1, CURDATE(), :s)'
        );
        $stmt->execute(['d' => $dogId, 'o' => $ownerId, 's' => $source]);
    }

    /**
     * Rows for CSV export (single query, contacts aggregated - no N+1).
     *
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(string $where, array $params, string $orderBy, int $cap = 50000): array
    {
        $cap = max(1, $cap);
        $stmt = $this->pdo()->prepare(
            "SELECT d.name, d.kennel_name, d.sex, d.pedigree_number, d.chip_number,
                    d.birth_date, d.death_date, d.death_cause, d.color, d.test_group,
                    d.health_summary, d.sample_received_at,
                    b.slug AS breed_slug,
                    o.display_name AS owner_name, o.address AS owner_address,
                    (SELECT email FROM owner_emails e WHERE e.owner_id = o.id AND e.is_primary = 1 LIMIT 1) AS owner_primary_email,
                    (SELECT GROUP_CONCAT(email SEPARATOR ';') FROM owner_emails e WHERE e.owner_id = o.id AND e.is_primary = 0) AS owner_secondary_emails,
                    (SELECT GROUP_CONCAT(phone SEPARATOR ';') FROM owner_phones p WHERE p.owner_id = o.id) AS owner_phones
             FROM dogs d
             JOIN breeds b ON b.id = d.breed_id
             LEFT JOIN dog_owners do2 ON do2.dog_id = d.id AND do2.is_current = 1
             LEFT JOIN owners o ON o.id = do2.owner_id
             WHERE {$where}
             ORDER BY {$orderBy}
             LIMIT {$cap}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
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

    /** @return array<string, mixed>|null vztah dog_owners pro daneho majitele */
    public function relation(int $dogId, int $ownerId): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM dog_owners WHERE dog_id = :d AND owner_id = :o
             ORDER BY is_current DESC, id DESC LIMIT 1'
        );
        $stmt->execute(['d' => $dogId, 'o' => $ownerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function confirmOwnership(int $dogId, int $ownerId): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE dog_owners SET confirmed_at = NOW()
             WHERE dog_id = :d AND owner_id = :o AND is_current = 1'
        );
        $stmt->execute(['d' => $dogId, 'o' => $ownerId]);
    }

    /**
     * Nastavi stav naziva/umrti. alive=true -> vycisti datum umrti. alive=false
     * -> ulozi report (auditovany original) a propise do psa.
     */
    public function setAliveStatus(int $dogId, ?int $ownerId, bool $alive, ?string $deathDateIso, ?string $note, string $source = 'owner'): void
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            if ($alive) {
                $stmt = $pdo->prepare('UPDATE dogs SET death_date = NULL, death_cause = NULL, updated_at = NOW() WHERE id = :d');
                $stmt->execute(['d' => $dogId]);
            } else {
                $report = $pdo->prepare(
                    'INSERT INTO dog_death_reports (dog_id, owner_id, death_date, note, source)
                     VALUES (:d, :o, :dd, :note, :src)'
                );
                $report->execute(['d' => $dogId, 'o' => $ownerId, 'dd' => $deathDateIso, 'note' => $note, 'src' => $source]);

                $stmt = $pdo->prepare('UPDATE dogs SET death_date = :dd, updated_at = NOW() WHERE id = :d');
                $stmt->execute(['dd' => $deathDateIso, 'd' => $dogId]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function addHealthDocument(int $dogId, ?int $ownerId, int $fileId, ?string $docType, ?string $docDateIso, ?string $note): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO health_documents (dog_id, owner_id, file_id, document_type, document_date, note)
             VALUES (:d, :o, :f, :t, :dd, :n)'
        );
        $stmt->execute(['d' => $dogId, 'o' => $ownerId, 'f' => $fileId, 't' => $docType, 'dd' => $docDateIso, 'n' => $note]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function healthDocuments(int $dogId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT hd.id, hd.document_type, hd.document_date, hd.note, hd.created_at,
                    f.id AS file_id, f.original_name, f.mime_type, f.size
             FROM health_documents hd
             LEFT JOIN files f ON f.id = hd.file_id
             WHERE hd.dog_id = :d
             ORDER BY hd.created_at DESC'
        );
        $stmt->execute(['d' => $dogId]);
        return $stmt->fetchAll();
    }

    private static function nv(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
