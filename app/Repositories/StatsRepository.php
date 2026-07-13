<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\I18n;
use PDO;

/** Agregovane statistiky pro klubovy dashboard - vse scope-ovane na jedno plemeno. */
final class StatsRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    /** @return array{total:int, alive:int, dead:int} */
    public function dogCounts(int $breedId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(death_date IS NULL) AS alive,
                    SUM(death_date IS NOT NULL) AS dead
             FROM dogs WHERE breed_id = :b'
        );
        $stmt->execute(['b' => $breedId]);
        $row = $stmt->fetch() ?: [];
        return [
            'total' => (int) ($row['total'] ?? 0),
            'alive' => (int) ($row['alive'] ?? 0),
            'dead' => (int) ($row['dead'] ?? 0),
        ];
    }

    public function avgAgeYears(int $breedId): ?float
    {
        $stmt = $this->pdo()->prepare(
            'SELECT AVG(TIMESTAMPDIFF(DAY, birth_date, COALESCE(death_date, CURDATE())) / 365.25)
             FROM dogs WHERE breed_id = :b AND birth_date IS NOT NULL'
        );
        $stmt->execute(['b' => $breedId]);
        $v = $stmt->fetchColumn();
        return $v === null || $v === false ? null : round((float) $v, 1);
    }

    /** @return array{b0:int, b1:int, b2:int, b3:int} vekove skupiny <1 / 1-3 / 3-7 / 7+ let */
    public function ageBuckets(int $breedId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT
                SUM(age < 1) AS b0,
                SUM(age >= 1 AND age < 3) AS b1,
                SUM(age >= 3 AND age < 7) AS b2,
                SUM(age >= 7) AS b3
             FROM (
                SELECT TIMESTAMPDIFF(DAY, birth_date, COALESCE(death_date, CURDATE())) / 365.25 AS age
                FROM dogs WHERE breed_id = :b AND birth_date IS NOT NULL
             ) t'
        );
        $stmt->execute(['b' => $breedId]);
        $row = $stmt->fetch() ?: [];
        return [
            'b0' => (int) ($row['b0'] ?? 0),
            'b1' => (int) ($row['b1'] ?? 0),
            'b2' => (int) ($row['b2'] ?? 0),
            'b3' => (int) ($row['b3'] ?? 0),
        ];
    }

    /**
     * Cetnost pricin umrti. Label z ciselniku (death_causes) prelozi do jazyka diveka
     * pres DB translations (klic = id); free-text/neuvedeno ma code = '' a zustava.
     *
     * @return array<int, array{code:string, cause:string, c:int}>
     */
    public function deathCauses(int $breedId): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT COALESCE(dc.code, '') AS code,
                    MIN(dc.id) AS cause_id,
                    COALESCE(d.death_cause, '') AS cause,
                    COUNT(*) AS c
             FROM dogs d
             LEFT JOIN death_causes dc ON dc.id = d.death_cause_id
             WHERE d.breed_id = :b AND d.death_date IS NOT NULL
             GROUP BY code, cause ORDER BY c DESC LIMIT 20"
        );
        $stmt->execute(['b' => $breedId]);
        $rows = $stmt->fetchAll();

        if (I18n::locale() !== I18n::defaultLocale()) {
            $ids = [];
            foreach ($rows as $r) {
                if ((int) ($r['cause_id'] ?? 0) > 0) {
                    $ids[] = (int) $r['cause_id'];
                }
            }
            if ($ids !== []) {
                $tx = (new TranslationRepository())->allForFields(DeathCauseRepository::ENTITY, ['label'], $ids, I18n::locale());
                foreach ($rows as &$r) {
                    $cid = (int) ($r['cause_id'] ?? 0);
                    if ($cid > 0 && isset($tx[$cid]['label']) && $tx[$cid]['label'] !== '') {
                        $r['cause'] = $tx[$cid]['label'];
                    }
                }
                unset($r);
            }
        }
        return $rows;
    }

    /** @return array<int, array{gene_symbol:string, marker_code:string, genotype:string, c:int}> */
    public function geneticDistribution(int $breedId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT ge.symbol AS gene_symbol, m.marker_code, g.genotype, COUNT(*) AS c
             FROM dog_genotypes g
             JOIN genetic_markers m ON m.id = g.marker_id
             JOIN genes ge ON ge.id = m.gene_id
             WHERE g.breed_id = :b
             GROUP BY m.id, g.genotype
             ORDER BY m.marker_code ASC, c DESC'
        );
        $stmt->execute(['b' => $breedId]);
        return $stmt->fetchAll();
    }

    public function dogsCount(int $breedId): int
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM dogs WHERE breed_id = :b');
        $stmt->execute(['b' => $breedId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Seznam psu pro klub: se jmenem majitele, ale BEZ kontaktnich udaju.
     *
     * @return array<int, array<string, mixed>>
     */
    public function dogsForClub(int $breedId, int $limit, int $offset): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);
        $stmt = $this->pdo()->prepare(
            "SELECT d.id, d.name, d.sex, d.birth_date, d.death_date,
                    o.display_name AS owner_name
             FROM dogs d
             LEFT JOIN dog_owners do2 ON do2.dog_id = d.id AND do2.is_current = 1
             LEFT JOIN owners o ON o.id = do2.owner_id
             WHERE d.breed_id = :b
             ORDER BY d.name ASC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute(['b' => $breedId]);
        return $stmt->fetchAll();
    }
}
