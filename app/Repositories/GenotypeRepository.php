<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class GenotypeRepository
{
    private const SORTS = [
        'dog' => 'd.name',
        'marker' => 'm.marker_code',
        'genotype' => 'g.genotype',
        'tested' => 't.tested_at',
        'created' => 'g.created_at',
    ];

    private function pdo(): PDO
    {
        return Database::pdo();
    }

    public function createTest(int $dogId, ?string $lab, ?string $testedAt, string $source, ?int $fileId, ?string $notes): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO genetic_tests (dog_id, lab_name, tested_at, source, source_file_id, notes)
             VALUES (:d, :l, :t, :s, :f, :n)'
        );
        $stmt->execute(['d' => $dogId, 'l' => $lab, 't' => $testedAt ?: null, 's' => $source, 'f' => $fileId, 'n' => $notes]);
        return (int) $this->pdo()->lastInsertId();
    }

    public function upsertGenotype(int $dogId, ?int $breedId, int $markerId, ?string $a1, ?string $a2, string $genotype, ?int $testId, string $status = 'imported'): void
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO dog_genotypes (dog_id, breed_id, marker_id, allele_1, allele_2, genotype, genetic_test_id, validation_status)
             VALUES (:d, :b, :m, :a1, :a2, :g, :t, :st)
             ON DUPLICATE KEY UPDATE
                allele_1 = VALUES(allele_1), allele_2 = VALUES(allele_2), genotype = VALUES(genotype),
                genetic_test_id = VALUES(genetic_test_id), validation_status = VALUES(validation_status),
                updated_at = NOW()'
        );
        $stmt->execute(['d' => $dogId, 'b' => $breedId, 'm' => $markerId, 'a1' => $a1, 'a2' => $a2, 'g' => $genotype, 't' => $testId, 'st' => $status]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{where: string, params: array<string, mixed>}
     */
    private function buildWhere(array $filters, ?int $breedId): array
    {
        $where = [];
        $params = [];
        if ($breedId !== null) {
            $where[] = 'g.breed_id = :breed';
            $params['breed'] = $breedId;
        }
        if (!empty($filters['marker_id'])) {
            $where[] = 'g.marker_id = :marker';
            $params['marker'] = (int) $filters['marker_id'];
        }
        $gt = trim((string) ($filters['genotype'] ?? ''));
        if ($gt !== '') {
            $where[] = 'g.genotype = :gt';
            $params['gt'] = strtoupper($gt);
        }
        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = 'd.name LIKE :q';
            $params['q'] = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
        }
        return ['where' => $where === [] ? '1=1' : implode(' AND ', $where), 'params' => $params];
    }

    public static function orderBy(?string $sort, ?string $dir): string
    {
        $col = self::SORTS[$sort] ?? 'd.name';
        $dir = strtolower((string) $dir) === 'desc' ? 'DESC' : 'ASC';
        return $col . ' ' . $dir . ', g.id ' . $dir;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function list(array $filters, ?int $breedId, string $orderBy, int $limit, int $offset): array
    {
        $built = $this->buildWhere($filters, $breedId);
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $stmt = $this->pdo()->prepare(
            "SELECT g.id, g.genotype, g.validation_status,
                    d.id AS dog_id, d.name AS dog_name, b.name AS breed_name,
                    m.marker_code, ge.symbol AS gene_symbol, t.tested_at, t.lab_name
             FROM dog_genotypes g
             JOIN dogs d ON d.id = g.dog_id
             JOIN genetic_markers m ON m.id = g.marker_id
             JOIN genes ge ON ge.id = m.gene_id
             LEFT JOIN breeds b ON b.id = g.breed_id
             LEFT JOIN genetic_tests t ON t.id = g.genetic_test_id
             WHERE {$built['where']}
             ORDER BY {$orderBy}
             LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute($built['params']);
        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $filters */
    public function count(array $filters, ?int $breedId): int
    {
        $built = $this->buildWhere($filters, $breedId);
        $stmt = $this->pdo()->prepare(
            "SELECT COUNT(*) FROM dog_genotypes g JOIN dogs d ON d.id = g.dog_id WHERE {$built['where']}"
        );
        $stmt->execute($built['params']);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function exportRows(array $filters, ?int $breedId, string $orderBy, int $cap = 100000): array
    {
        return $this->list($filters, $breedId, $orderBy, $cap, 0);
    }

    /** @return array<int, array<string, mixed>> genotypy jednoho psa (pro detail) */
    public function byDog(int $dogId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT g.genotype, g.validation_status, m.marker_code, ge.symbol AS gene_symbol, t.tested_at
             FROM dog_genotypes g
             JOIN genetic_markers m ON m.id = g.marker_id
             JOIN genes ge ON ge.id = m.gene_id
             LEFT JOIN genetic_tests t ON t.id = g.genetic_test_id
             WHERE g.dog_id = :d
             ORDER BY m.marker_code ASC'
        );
        $stmt->execute(['d' => $dogId]);
        return $stmt->fetchAll();
    }
}
