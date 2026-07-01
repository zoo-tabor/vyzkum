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

    public function upsertGenotype(int $dogId, ?int $breedId, int $markerId, ?string $a1, ?string $a2, string $genotype, ?int $testId, string $status = 'imported', ?int $geneId = null): void
    {
        if ($geneId === null) {
            $lookup = $this->pdo()->prepare('SELECT gene_id FROM genetic_markers WHERE id = :m LIMIT 1');
            $lookup->execute(['m' => $markerId]);
            $found = $lookup->fetchColumn();
            $geneId = $found === false ? null : (int) $found;
        }

        $stmt = $this->pdo()->prepare(
            'INSERT INTO dog_genotypes (dog_id, breed_id, marker_id, gene_id, allele_1, allele_2, genotype, genetic_test_id, validation_status)
             VALUES (:d, :b, :m, :ge, :a1, :a2, :g, :t, :st)
             ON DUPLICATE KEY UPDATE
                gene_id = VALUES(gene_id), allele_1 = VALUES(allele_1), allele_2 = VALUES(allele_2),
                genotype = VALUES(genotype), genetic_test_id = VALUES(genetic_test_id),
                validation_status = VALUES(validation_status), updated_at = NOW()'
        );
        $stmt->execute(['d' => $dogId, 'b' => $breedId, 'm' => $markerId, 'ge' => $geneId, 'a1' => $a1, 'a2' => $a2, 'g' => $genotype, 't' => $testId, 'st' => $status]);
    }

    /**
     * Geny sledovane u plemene (maji genotypy). Kdyz breedId null, vsechny geny s genotypy.
     *
     * @return array<int, array{id:int, symbol:string}>
     */
    public function genesForBreed(?int $breedId): array
    {
        $sql = 'SELECT DISTINCT ge.id, ge.symbol
                FROM dog_genotypes g JOIN genes ge ON ge.id = g.gene_id
                WHERE g.gene_id IS NOT NULL';
        $params = [];
        if ($breedId !== null) {
            $sql .= ' AND g.breed_id = :b';
            $params['b'] = $breedId;
        }
        $sql .= ' ORDER BY ge.symbol ASC';
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        return array_map(static fn (array $r): array => ['id' => (int) $r['id'], 'symbol' => (string) $r['symbol']], $stmt->fetchAll());
    }

    /**
     * Psi, kteri maji nejaky genotyp (volitelne dle plemene) - radky dashboardu.
     *
     * @return array<int, array<string, mixed>>
     */
    public function dogsWithGenotypes(?int $breedId): array
    {
        $sub = 'SELECT DISTINCT dog_id FROM dog_genotypes WHERE gene_id IS NOT NULL';
        $params = [];
        if ($breedId !== null) {
            $sub .= ' AND breed_id = :b';
            $params['b'] = $breedId;
        }
        $stmt = $this->pdo()->prepare(
            "SELECT d.id, d.name, b.name AS breed_name
             FROM dogs d JOIN breeds b ON b.id = d.breed_id
             WHERE d.id IN ({$sub})
             ORDER BY d.name ASC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * @param array<int, int> $dogIds
     * @return array<int, array<int, string>> dog_id => [gene_id => genotype]
     */
    public function genotypesByDogGene(array $dogIds): array
    {
        $ids = implode(',', array_values(array_unique(array_map('intval', $dogIds))));
        if ($ids === '') {
            return [];
        }
        $rows = $this->pdo()->query(
            "SELECT dog_id, gene_id, genotype FROM dog_genotypes WHERE gene_id IS NOT NULL AND dog_id IN ({$ids})"
        )->fetchAll();
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['dog_id']][(int) $r['gene_id']] = (string) $r['genotype'];
        }
        return $map;
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
