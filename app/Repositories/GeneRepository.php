<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class GeneRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    /** @return array<int, array<string, mixed>> */
    public function genes(): array
    {
        return $this->pdo()->query(
            'SELECT ge.*, (SELECT COUNT(*) FROM genetic_markers m WHERE m.gene_id = ge.id) AS marker_count
             FROM genes ge ORDER BY ge.symbol ASC'
        )->fetchAll();
    }

    public function ensureGene(string $symbol, ?string $name = null): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO genes (symbol, name) VALUES (:s, :n)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
        );
        $stmt->execute(['s' => $symbol, 'n' => $name]);
        return (int) $this->pdo()->lastInsertId();
    }

    public function createGene(string $symbol, ?string $name, ?string $description, ?string $note = null): int
    {
        $stmt = $this->pdo()->prepare('INSERT INTO genes (symbol, name, description, note) VALUES (:s, :n, :d, :note)');
        $stmt->execute(['s' => $symbol, 'n' => $name, 'd' => $description, 'note' => $note]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findGene(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM genes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateGene(int $id, string $symbol, ?string $name, ?string $description, ?string $note): void
    {
        $stmt = $this->pdo()->prepare('UPDATE genes SET symbol = :s, name = :n, description = :d, note = :note WHERE id = :id');
        $stmt->execute(['id' => $id, 's' => $symbol, 'n' => $name, 'd' => $description, 'note' => $note]);
    }

    /** @return array<int, array<string, mixed>> */
    public function markers(): array
    {
        return $this->pdo()->query(
            'SELECT m.*, ge.symbol AS gene_symbol
             FROM genetic_markers m JOIN genes ge ON ge.id = m.gene_id
             ORDER BY ge.symbol ASC, m.marker_code ASC'
        )->fetchAll();
    }

    public function createMarker(int $geneId, string $code, ?string $locus, ?string $ref, ?string $alt, ?string $allowed): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO genetic_markers (gene_id, marker_code, locus, reference_allele, alternate_allele, allowed_values)
             VALUES (:g, :c, :l, :r, :a, :av)'
        );
        $stmt->execute(['g' => $geneId, 'c' => $code, 'l' => $locus, 'r' => $ref, 'a' => $alt, 'av' => $allowed]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findMarker(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT m.*, ge.symbol AS gene_symbol FROM genetic_markers m
             JOIN genes ge ON ge.id = m.gene_id WHERE m.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateMarker(int $id, int $geneId, string $code, ?string $locus, ?string $ref, ?string $alt, ?string $allowed): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE genetic_markers SET gene_id = :g, marker_code = :c, locus = :l,
                    reference_allele = :r, alternate_allele = :a, allowed_values = :av
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id, 'g' => $geneId, 'c' => $code, 'l' => $locus, 'r' => $ref, 'a' => $alt, 'av' => $allowed]);
    }

    public function findMarkerByCode(string $code): ?int
    {
        $stmt = $this->pdo()->prepare('SELECT id FROM genetic_markers WHERE marker_code = :c LIMIT 1');
        $stmt->execute(['c' => $code]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /** Najde marker podle kodu; pokud neexistuje, zalozi gen (symbol=kod) + marker. */
    public function ensureMarker(string $code): int
    {
        $existing = $this->findMarkerByCode($code);
        if ($existing !== null) {
            return $existing;
        }
        $geneId = $this->ensureGene($code);
        return $this->createMarker($geneId, $code, null, null, null, null);
    }

    /**
     * Geny s reprezentativnim markerem (jeden na gen) - pro rucni zadani vsech genu naraz.
     *
     * @return array<int, array{gene_id:int, symbol:string, marker_id:int}>
     */
    public function genesWithMarker(): array
    {
        $rows = $this->pdo()->query(
            'SELECT ge.id AS gene_id, ge.symbol, MIN(m.id) AS marker_id
             FROM genes ge JOIN genetic_markers m ON m.gene_id = ge.id
             GROUP BY ge.id, ge.symbol
             ORDER BY ge.symbol ASC'
        )->fetchAll();
        return array_map(static fn (array $r): array => [
            'gene_id' => (int) $r['gene_id'],
            'symbol' => (string) $r['symbol'],
            'marker_id' => (int) $r['marker_id'],
        ], $rows);
    }

    /** @return array<int, array<string, mixed>> markery pro filtr/vyber */
    public function markersForSelect(): array
    {
        return $this->pdo()->query(
            'SELECT m.id, m.marker_code, ge.symbol AS gene_symbol
             FROM genetic_markers m JOIN genes ge ON ge.id = m.gene_id
             ORDER BY m.marker_code ASC'
        )->fetchAll();
    }
}
