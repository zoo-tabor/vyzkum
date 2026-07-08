<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\I18n;
use PDO;

/**
 * Hierarchicky ciselnik pricin umrti. Seznam je bud specificky pro plemeno,
 * nebo globalni (breed_id NULL) - momentalne globalni seznam pro cavaliera.
 */
final class DeathCauseRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    /**
     * Ploche radky taxonomie: breed-specificke, kdyz existuji, jinak globalni.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rowsForBreed(?int $breedId): array
    {
        if ($breedId !== null) {
            $stmt = $this->pdo()->prepare(
                'SELECT id, parent_id, code, label, has_note FROM death_causes WHERE breed_id = :b ORDER BY position ASC, id ASC'
            );
            $stmt->execute(['b' => $breedId]);
            $rows = $stmt->fetchAll();
            if ($rows !== []) {
                return $rows;
            }
        }
        return $this->pdo()
            ->query('SELECT id, parent_id, code, label, has_note FROM death_causes WHERE breed_id IS NULL ORDER BY position ASC, id ASC')
            ->fetchAll();
    }

    /**
     * Vnoreny strom pro JS (uzly s children a has_note). Labely jsou prelozene do
     * aktualniho jazyka (overlay dle stabilniho kodu), kanonicka data zustavaji.
     *
     * @return array<int, array<string, mixed>>
     */
    public function treeForBreed(?int $breedId): array
    {
        $childrenMap = [];
        foreach ($this->rowsForBreed($breedId) as $r) {
            $pid = $r['parent_id'] !== null ? (int) $r['parent_id'] : 0;
            $childrenMap[$pid][] = $r;
        }

        $build = static function (int $parentId) use (&$build, $childrenMap): array {
            $out = [];
            foreach ($childrenMap[$parentId] ?? [] as $r) {
                $out[] = [
                    'id' => (int) $r['id'],
                    'label' => I18n::td('death_causes', (string) $r['code'], (string) $r['label']),
                    'has_note' => (int) $r['has_note'] === 1,
                    'children' => $build((int) $r['id']),
                ];
            }
            return $out;
        };

        return $build(0);
    }

    /**
     * Podstrom NEMOCI (vetev s korenem code='1') pro zdravotni historii v dotazniku.
     * Vraci kategorie (1.1..) s vnorenymi listy nemoci; labely prelozene, u kazdeho uzlu
     * code + is_leaf + has_note. Vynechava Stari/Nehoda/Jine (koreny 2/3/4).
     *
     * @return array<int, array<string, mixed>>
     */
    public function diseaseTreeForBreed(?int $breedId): array
    {
        $rows = $this->rowsForBreed($breedId);
        $rootId = null;
        $childrenMap = [];
        foreach ($rows as $r) {
            if ((string) $r['code'] === '1') {
                $rootId = (int) $r['id'];
            }
            $pid = $r['parent_id'] !== null ? (int) $r['parent_id'] : 0;
            $childrenMap[$pid][] = $r;
        }
        if ($rootId === null) {
            return [];
        }

        $build = static function (int $parentId) use (&$build, $childrenMap): array {
            $out = [];
            foreach ($childrenMap[$parentId] ?? [] as $r) {
                $children = $build((int) $r['id']);
                $out[] = [
                    'id' => (int) $r['id'],
                    'code' => (string) $r['code'],
                    'label' => I18n::td('death_causes', (string) $r['code'], (string) $r['label']),
                    'has_note' => (int) $r['has_note'] === 1,
                    'is_leaf' => $children === [],
                    'children' => $children,
                ];
            }
            return $out;
        };

        return $build($rootId);
    }

    /**
     * Prelozeny label pro dane id (napric plemeny) - pro zobrazeni ulozene priciny
     * v aktualnim jazyce. Vraci cesky zdroj jako fallback, nebo null kdyz id neexistuje.
     */
    public function displayLabel(int $id): ?string
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->pdo()->prepare('SELECT code, label FROM death_causes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }
        return I18n::td('death_causes', (string) $row['code'], (string) $row['label']);
    }

    /**
     * Vrati radek listu (bez potomku) v dane taxonomii, jinak null.
     * Zabranuje vyberu nekonecne volby / cizi polozky.
     *
     * @return array<string, mixed>|null
     */
    public function findLeaf(int $id, ?int $breedId): ?array
    {
        $rows = $this->rowsForBreed($breedId);
        $byId = [];
        $hasChild = [];
        foreach ($rows as $r) {
            $byId[(int) $r['id']] = $r;
            if ($r['parent_id'] !== null) {
                $hasChild[(int) $r['parent_id']] = true;
            }
        }
        if (!isset($byId[$id]) || isset($hasChild[$id])) {
            return null;
        }
        return $byId[$id];
    }
}
