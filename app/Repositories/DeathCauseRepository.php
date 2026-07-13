<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\I18n;
use PDO;

/**
 * Hierarchicky ciselnik pricin umrti per plemeno (editovatelny z Nastaveni).
 * Preklady labelu jsou v DB tabulce translations (entity 'death_cause', pole
 * 'label', klic = id radku) - admin je edituje z UI stejne jako plemena/dotazniky.
 * Cesky label ve sloupci `label` je kanonicky zdroj; chybejici/prazdny preklad
 * spadne zpet na nej. Kod (code) je NEMENNY stabilni klic v ramci plemene.
 */
final class DeathCauseRepository
{
    public const ENTITY = 'death_cause';

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
     * Prekryje label prekladem do aktualniho jazyka (in-place, bez N+1). Kanonicka
     * ceska hodnota zustava fallbackem. Pro cestinu (zdroj) je no-op.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    private function translate(array &$rows): void
    {
        (new TranslationRepository())->apply(self::ENTITY, $rows, ['label'], I18n::locale());
    }

    /**
     * Vnoreny strom pro JS (uzly s children a has_note). Labely prelozene do
     * aktualniho jazyka, kanonicka data zustavaji.
     *
     * @return array<int, array<string, mixed>>
     */
    public function treeForBreed(?int $breedId): array
    {
        $rows = $this->rowsForBreed($breedId);
        $this->translate($rows);

        $childrenMap = [];
        foreach ($rows as $r) {
            $pid = $r['parent_id'] !== null ? (int) $r['parent_id'] : 0;
            $childrenMap[$pid][] = $r;
        }

        $build = static function (int $parentId) use (&$build, $childrenMap): array {
            $out = [];
            foreach ($childrenMap[$parentId] ?? [] as $r) {
                $out[] = [
                    'id' => (int) $r['id'],
                    'label' => (string) $r['label'],
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
        $this->translate($rows);

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
                    'label' => (string) $r['label'],
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
        $stmt = $this->pdo()->prepare('SELECT id, label FROM death_causes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }
        $rows = [$row];
        $this->translate($rows);
        return (string) $rows[0]['label'];
    }

    /**
     * Vrati radek listu (bez potomku) v dane taxonomii, jinak null.
     * Zabranuje vyberu nekonecne volby / cizi polozky. Label je kanonicky (cesky).
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

    // ----- Editor (CRUD per plemeno) -----

    /**
     * Jeden radek vcetne struktury (breed_id, parent_id, code, position) - pro editaci.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT id, breed_id, parent_id, code, label, has_note, position FROM death_causes WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Plochy strom VLASTNICH radku plemene (bez globalniho fallbacku) v DFS poradi
     * (dle position v ramci sourozencu), s hloubkou a priznakem potomku. Kanonicke
     * (ceske) labely - editor upravuje zdroj, ne preklad.
     *
     * @return array<int, array<string, mixed>>
     */
    public function editorTree(int $breedId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT id, breed_id, parent_id, code, label, has_note, position
             FROM death_causes WHERE breed_id = :b ORDER BY position ASC, id ASC'
        );
        $stmt->execute(['b' => $breedId]);
        $rows = $stmt->fetchAll();

        $childrenMap = [];
        foreach ($rows as $r) {
            $pid = $r['parent_id'] !== null ? (int) $r['parent_id'] : 0;
            $childrenMap[$pid][] = $r;
        }

        $out = [];
        $walk = static function (int $parentId, int $depth) use (&$walk, $childrenMap, &$out): void {
            foreach ($childrenMap[$parentId] ?? [] as $r) {
                $r['depth'] = $depth;
                $r['has_children'] = isset($childrenMap[(int) $r['id']]);
                $out[] = $r;
                $walk((int) $r['id'], $depth + 1);
            }
        };
        $walk(0, 0);
        return $out;
    }

    /** Kolik ma uzel primych potomku (blokuje smazani, dokud nejsou pryc). */
    public function childrenCount(int $id): int
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM death_causes WHERE parent_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    /** Kolik psu ma tuto pricinu prirazenou (blokuje smazani, aby nevznikl orphan). */
    public function dogUsageCount(int $id): int
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM dogs WHERE death_cause_id = :id');
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn();
    }

    /** Vytvori uzel (auto kod + poradi na konec). Vraci nove id. */
    public function create(int $breedId, ?int $parentId, string $label, bool $hasNote): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO death_causes (breed_id, parent_id, code, label, has_note, position)
             VALUES (:b, :p, :c, :l, :h, :pos)'
        );
        $stmt->execute([
            'b' => $breedId,
            'p' => $parentId,
            'c' => $this->nextCode($breedId, $parentId),
            'l' => $label,
            'h' => $hasNote ? 1 : 0,
            'pos' => $this->nextPosition($breedId),
        ]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** Upravi label + has_note. Kod, rodic ani plemeno se nemeni (stabilni klic). */
    public function update(int $id, string $label, bool $hasNote): void
    {
        $stmt = $this->pdo()->prepare('UPDATE death_causes SET label = :l, has_note = :h WHERE id = :id');
        $stmt->execute(['l' => $label, 'h' => $hasNote ? 1 : 0, 'id' => $id]);
    }

    /** Smaze uzel a jeho preklady (volajici hlida, ze nema potomky ani pouziti). */
    public function delete(int $id): void
    {
        $this->pdo()->prepare('DELETE FROM death_causes WHERE id = :id')->execute(['id' => $id]);
        (new TranslationRepository())->deleteEntity(self::ENTITY, $id);
    }

    /** Presun uzlu nahoru/dolu MEZI SOUROZENCI (prohodi position; rodic zustava). */
    public function move(int $id, string $dir): void
    {
        $node = $this->findById($id);
        if ($node === null) {
            return;
        }
        $parentId = $node['parent_id'] !== null ? (int) $node['parent_id'] : null;

        $sql = 'SELECT id, position FROM death_causes WHERE breed_id = :b AND '
            . ($parentId === null ? 'parent_id IS NULL' : 'parent_id = :p')
            . ' ORDER BY position ASC, id ASC';
        $stmt = $this->pdo()->prepare($sql);
        $params = ['b' => (int) $node['breed_id']];
        if ($parentId !== null) {
            $params['p'] = $parentId;
        }
        $stmt->execute($params);
        $siblings = $stmt->fetchAll();

        $idx = null;
        foreach ($siblings as $i => $s) {
            if ((int) $s['id'] === $id) {
                $idx = $i;
                break;
            }
        }
        if ($idx === null) {
            return;
        }
        $swap = $dir === 'up' ? $idx - 1 : $idx + 1;
        if ($swap < 0 || $swap >= count($siblings)) {
            return;
        }

        $a = $siblings[$idx];
        $b = $siblings[$swap];
        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            $u = $pdo->prepare('UPDATE death_causes SET position = :pos WHERE id = :id');
            $u->execute(['pos' => (int) $b['position'], 'id' => (int) $a['id']]);
            $u->execute(['pos' => (int) $a['position'], 'id' => (int) $b['id']]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function nextPosition(int $breedId): int
    {
        $stmt = $this->pdo()->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM death_causes WHERE breed_id = :b');
        $stmt->execute(['b' => $breedId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Vygeneruje unikatni kod pro novy uzel: 'kodRodice.k' (koren jen 'k'), kde k je
     * prvni volne cislo pod prefixem. Kod je opaque stabilni klic - hodnota nemusi
     * byt souvisla, jen unikatni v ramci plemene (UNIQUE breed_id, code).
     */
    private function nextCode(int $breedId, ?int $parentId): string
    {
        $prefix = '';
        if ($parentId !== null) {
            $parent = $this->findById($parentId);
            if ($parent !== null) {
                $prefix = (string) $parent['code'] . '.';
            }
        }
        $k = 1;
        while ($this->codeExists($breedId, $prefix . $k)) {
            $k++;
        }
        return $prefix . $k;
    }

    private function codeExists(int $breedId, string $code): bool
    {
        $stmt = $this->pdo()->prepare('SELECT 1 FROM death_causes WHERE breed_id = :b AND code = :c LIMIT 1');
        $stmt->execute(['b' => $breedId, 'c' => $code]);
        return (bool) $stmt->fetchColumn();
    }
}
