<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\I18n;
use PDO;

/**
 * Obecna prekladova vrstva pro admin-authored obsah (dotazniky ap.). Kanonicka
 * (ceska) data zustavaji ve svych sloupcich; tady je jen preklad klicovany
 * radkovym id entity. Prazdny preklad = fallback na cesky zdroj.
 */
final class TranslationRepository
{
    // Typy entit a jejich prekladatelna pole.
    public const FORM_DEFINITION = 'form_definition'; // name, description
    public const FORM_QUESTION = 'form_question';     // label, help_text
    public const FORM_OPTION = 'form_option';         // label

    /** @var bool|null cache existence tabulky (deploy kodu muze predbehnout SQL migraci) */
    private static ?bool $enabled = null;

    private function pdo(): PDO
    {
        return Database::pdo();
    }

    /**
     * Existuje uz tabulka translations? Kdyz jeste ne (migrace nespustena), cela
     * prekladova vrstva se chova jako no-op (fallback na cestinu) misto padu.
     */
    private function enabled(): bool
    {
        if (self::$enabled === null) {
            try {
                $this->pdo()->query('SELECT 1 FROM translations LIMIT 1');
                self::$enabled = true;
            } catch (\Throwable $e) {
                self::$enabled = false;
            }
        }
        return self::$enabled;
    }

    /**
     * Prekryje pole v $rows prekladem pro $locale (in-place, bez N+1). Kanonicka
     * ceska hodnota zustava fallbackem, kdyz preklad chybi/je prazdny.
     *
     * @param array<int, array<string, mixed>> $rows
     * @param array<int, string>               $fields sloupce k prelozeni
     */
    public function apply(string $entityType, array &$rows, array $fields, string $locale, string $idKey = 'id'): void
    {
        if ($locale === I18n::defaultLocale() || $rows === [] || $fields === []) {
            return;
        }
        $ids = [];
        foreach ($rows as $r) {
            $ids[] = (int) $r[$idKey];
        }
        $tx = $this->allForFields($entityType, $fields, $ids, $locale);
        foreach ($rows as &$r) {
            $id = (int) $r[$idKey];
            foreach ($fields as $f) {
                if (isset($tx[$id][$f]) && $tx[$id][$f] !== '') {
                    $r[$f] = $tx[$id][$f];
                }
            }
        }
        unset($r);
    }

    /**
     * Overlay jednoho radku (napr. definice dotazniku).
     *
     * @param array<string, mixed> $row
     * @param array<int, string>   $fields
     */
    public function applyRow(string $entityType, array &$row, array $fields, string $locale, string $idKey = 'id'): void
    {
        $rows = [$row];
        $this->apply($entityType, $rows, $fields, $locale, $idKey);
        $row = $rows[0];
    }

    /**
     * Overlay pro seskupene radky (napr. moznosti podle question_id): [gid => list].
     *
     * @param array<int, array<int, array<string, mixed>>> $grouped
     * @param array<int, string>                           $fields
     */
    public function applyGrouped(string $entityType, array &$grouped, array $fields, string $locale, string $idKey = 'id'): void
    {
        if ($locale === I18n::defaultLocale() || $grouped === [] || $fields === []) {
            return;
        }
        $ids = [];
        foreach ($grouped as $list) {
            foreach ($list as $r) {
                $ids[] = (int) $r[$idKey];
            }
        }
        $tx = $this->allForFields($entityType, $fields, $ids, $locale);
        foreach ($grouped as &$list) {
            foreach ($list as &$r) {
                $id = (int) $r[$idKey];
                foreach ($fields as $f) {
                    if (isset($tx[$id][$f]) && $tx[$id][$f] !== '') {
                        $r[$f] = $tx[$id][$f];
                    }
                }
            }
            unset($r);
        }
        unset($list);
    }

    /**
     * Batch nacteni prekladu: [entity_id => [field => text]] pro dane id/pole/locale.
     *
     * @param array<int, string> $fields
     * @param array<int, int>    $ids
     * @return array<int, array<string, string>>
     */
    public function allForFields(string $entityType, array $fields, array $ids, string $locale): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === [] || $fields === [] || !$this->enabled()) {
            return [];
        }
        $idPh = implode(',', array_fill(0, count($ids), '?'));
        $fPh = implode(',', array_fill(0, count($fields), '?'));
        $sql = "SELECT entity_id, field, text FROM translations
                WHERE entity_type = ? AND locale = ? AND field IN ({$fPh}) AND entity_id IN ({$idPh})";
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute(array_merge([$entityType, $locale], array_values($fields), $ids));

        $out = [];
        foreach ($stmt->fetchAll() as $r) {
            $out[(int) $r['entity_id']][(string) $r['field']] = (string) $r['text'];
        }
        return $out;
    }

    /**
     * Vsechny preklady jedne entity pro jeden jazyk (pro editacni obrazovku).
     *
     * @param array<int, int> $ids
     * @return array<int, array<string, string>> [entity_id => [field => text]]
     */
    public function allForEntities(string $entityType, array $ids, string $locale): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === [] || !$this->enabled()) {
            return [];
        }
        $idPh = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo()->prepare(
            "SELECT entity_id, field, text FROM translations
             WHERE entity_type = ? AND locale = ? AND entity_id IN ({$idPh})"
        );
        $stmt->execute(array_merge([$entityType, $locale], $ids));

        $out = [];
        foreach ($stmt->fetchAll() as $r) {
            $out[(int) $r['entity_id']][(string) $r['field']] = (string) $r['text'];
        }
        return $out;
    }

    /**
     * Vsechny jazykove verze jednoho pole entity: [locale => text]. Pro editacni
     * obrazovku, kde se zadava jedno pole napric vsemi jazyky najednou.
     *
     * @return array<string, string>
     */
    public function localesFor(string $entityType, int $entityId, string $field): array
    {
        if (!$this->enabled()) {
            return [];
        }
        $stmt = $this->pdo()->prepare(
            'SELECT locale, text FROM translations WHERE entity_type = :e AND entity_id = :i AND field = :f'
        );
        $stmt->execute(['e' => $entityType, 'i' => $entityId, 'f' => $field]);
        $out = [];
        foreach ($stmt->fetchAll() as $r) {
            $out[(string) $r['locale']] = (string) $r['text'];
        }
        return $out;
    }

    /**
     * Ulozi preklad (upsert). Prazdny text preklad SMAZE (=> fallback na cestinu).
     */
    public function set(string $entityType, int $entityId, string $field, string $locale, string $text): void
    {
        if (!$this->enabled()) {
            return;
        }
        $text = trim($text);
        if ($text === '') {
            $del = $this->pdo()->prepare(
                'DELETE FROM translations WHERE entity_type = :e AND entity_id = :i AND field = :f AND locale = :l'
            );
            $del->execute(['e' => $entityType, 'i' => $entityId, 'f' => $field, 'l' => $locale]);
            return;
        }
        $stmt = $this->pdo()->prepare(
            'INSERT INTO translations (entity_type, entity_id, field, locale, text)
             VALUES (:e, :i, :f, :l, :t)
             ON DUPLICATE KEY UPDATE text = VALUES(text), updated_at = NOW()'
        );
        $stmt->execute(['e' => $entityType, 'i' => $entityId, 'f' => $field, 'l' => $locale, 't' => $text]);
    }

    /**
     * Zkopiruje vsechny preklady entity (vsechny jazyky i pole) na nove id.
     * Pouziva se pri klonovani verze dotazniku (nova id otazek/moznosti).
     */
    public function copyEntity(string $entityType, int $fromId, int $toId): void
    {
        if (!$this->enabled()) {
            return;
        }
        $stmt = $this->pdo()->prepare(
            'INSERT IGNORE INTO translations (entity_type, entity_id, field, locale, text)
             SELECT entity_type, :to, field, locale, text
             FROM translations WHERE entity_type = :e AND entity_id = :from'
        );
        $stmt->execute(['to' => $toId, 'e' => $entityType, 'from' => $fromId]);
    }
}
