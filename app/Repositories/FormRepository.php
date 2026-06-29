<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class FormRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    // ----- Definitions -----

    public function createDefinition(int $breedId, string $name, ?string $description, ?int $userId): int
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO form_definitions (breed_id, name, description, status, created_by_user_id)
                 VALUES (:b, :n, :d, "draft", :u)'
            );
            $stmt->execute(['b' => $breedId, 'n' => $name, 'd' => $description, 'u' => $userId]);
            $defId = (int) $pdo->lastInsertId();

            $ver = $pdo->prepare('INSERT INTO form_versions (form_definition_id, version, status) VALUES (:d, 1, "draft")');
            $ver->execute(['d' => $defId]);

            $pdo->commit();
            return $defId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @return array<string, mixed>|null */
    public function findDefinition(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT d.*, b.name AS breed_name, b.slug AS breed_slug
             FROM form_definitions d JOIN breeds b ON b.id = d.breed_id
             WHERE d.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function listByBreed(?int $breedId): array
    {
        $where = $breedId !== null ? 'WHERE d.breed_id = :b' : '';
        $sql =
            "SELECT d.id, d.name, d.status, b.name AS breed_name,
                    (SELECT MAX(version) FROM form_versions v WHERE v.form_definition_id = d.id) AS latest_version,
                    (SELECT COUNT(*) FROM form_versions v WHERE v.form_definition_id = d.id AND v.status = 'published') AS published_count,
                    (SELECT COUNT(*) FROM form_versions v WHERE v.form_definition_id = d.id AND v.status = 'draft') AS draft_count
             FROM form_definitions d JOIN breeds b ON b.id = d.breed_id
             {$where}
             ORDER BY b.name ASC, d.name ASC";
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($breedId !== null ? ['b' => $breedId] : []);
        return $stmt->fetchAll();
    }

    // ----- Versions -----

    /** @return array<string, mixed>|null */
    public function draftVersion(int $defId): ?array
    {
        return $this->versionByStatus($defId, 'draft');
    }

    /** @return array<string, mixed>|null */
    public function publishedVersion(int $defId): ?array
    {
        return $this->versionByStatus($defId, 'published');
    }

    /** @return array<string, mixed>|null */
    private function versionByStatus(int $defId, string $status): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM form_versions WHERE form_definition_id = :d AND status = :s
             ORDER BY version DESC LIMIT 1'
        );
        $stmt->execute(['d' => $defId, 's' => $status]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function maxVersion(int $defId): int
    {
        $stmt = $this->pdo()->prepare('SELECT COALESCE(MAX(version), 0) FROM form_versions WHERE form_definition_id = :d');
        $stmt->execute(['d' => $defId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Vrati editovatelny draft. Pokud zadny neni, naklonuje posledni publikovanou
     * verzi do noveho draftu (vcetne otazek a moznosti).
     *
     * @return array<string, mixed>
     */
    public function ensureDraft(int $defId): array
    {
        $draft = $this->draftVersion($defId);
        if ($draft !== null) {
            return $draft;
        }

        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            $source = $this->publishedVersion($defId);
            $newVersion = $this->maxVersion($defId) + 1;

            $ins = $pdo->prepare('INSERT INTO form_versions (form_definition_id, version, status) VALUES (:d, :v, "draft")');
            $ins->execute(['d' => $defId, 'v' => $newVersion]);
            $newVersionId = (int) $pdo->lastInsertId();

            if ($source !== null) {
                $this->cloneQuestions((int) $source['id'], $newVersionId);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $this->draftVersion($defId) ?? [];
    }

    private function cloneQuestions(int $fromVersionId, int $toVersionId): void
    {
        $pdo = $this->pdo();
        $questions = $this->questions($fromVersionId);
        $insQ = $pdo->prepare(
            'INSERT INTO form_questions (form_version_id, question_key, label, help_text, type, is_required, position, config_json)
             VALUES (:v, :k, :l, :h, :t, :r, :p, :c)'
        );
        $insO = $pdo->prepare(
            'INSERT INTO form_question_options (question_id, option_key, label, position) VALUES (:q, :k, :l, :p)'
        );
        foreach ($questions as $q) {
            $insQ->execute([
                'v' => $toVersionId,
                'k' => $q['question_key'],
                'l' => $q['label'],
                'h' => $q['help_text'],
                't' => $q['type'],
                'r' => $q['is_required'],
                'p' => $q['position'],
                'c' => $q['config_json'],
            ]);
            $newQid = (int) $pdo->lastInsertId();
            foreach ($this->optionsFor((int) $q['id']) as $o) {
                $insO->execute(['q' => $newQid, 'k' => $o['option_key'], 'l' => $o['label'], 'p' => $o['position']]);
            }
        }
    }

    public function publish(int $defId): void
    {
        $draft = $this->draftVersion($defId);
        if ($draft === null) {
            throw new \RuntimeException('Neni co publikovat - chybi draft verze.');
        }
        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            $arch = $pdo->prepare(
                'UPDATE form_versions SET status = "archived", archived_at = NOW()
                 WHERE form_definition_id = :d AND status = "published"'
            );
            $arch->execute(['d' => $defId]);

            $pub = $pdo->prepare('UPDATE form_versions SET status = "published", published_at = NOW() WHERE id = :id');
            $pub->execute(['id' => $draft['id']]);

            $def = $pdo->prepare('UPDATE form_definitions SET status = "active", updated_at = NOW() WHERE id = :id');
            $def->execute(['id' => $defId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ----- Questions -----

    /** @return array<int, array<string, mixed>> */
    public function questions(int $versionId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM form_questions WHERE form_version_id = :v ORDER BY position ASC, id ASC'
        );
        $stmt->execute(['v' => $versionId]);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function findQuestion(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT q.*, v.status AS version_status, v.form_definition_id
             FROM form_questions q JOIN form_versions v ON v.id = q.form_version_id
             WHERE q.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @param array<string, mixed> $d */
    public function addQuestion(int $versionId, array $d): int
    {
        $pos = $this->pdo()->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM form_questions WHERE form_version_id = :v');
        $pos->execute(['v' => $versionId]);
        $position = (int) $pos->fetchColumn();

        $stmt = $this->pdo()->prepare(
            'INSERT INTO form_questions (form_version_id, question_key, label, help_text, type, is_required, position, config_json)
             VALUES (:v, :k, :l, :h, :t, :r, :p, :c)'
        );
        $stmt->execute([
            'v' => $versionId,
            'k' => $d['question_key'],
            'l' => $d['label'],
            'h' => $d['help_text'] ?? null,
            't' => $d['type'],
            'r' => !empty($d['is_required']) ? 1 : 0,
            'p' => $position,
            'c' => $d['config_json'] ?? null,
        ]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $d */
    public function updateQuestion(int $id, array $d): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE form_questions SET label = :l, help_text = :h, type = :t,
                    is_required = :r, config_json = :c
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'l' => $d['label'],
            'h' => $d['help_text'] ?? null,
            't' => $d['type'],
            'r' => !empty($d['is_required']) ? 1 : 0,
            'c' => $d['config_json'] ?? null,
        ]);
    }

    public function deleteQuestion(int $id): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM form_questions WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function move(int $id, string $dir): void
    {
        $q = $this->findQuestion($id);
        if ($q === null) {
            return;
        }
        $op = $dir === 'up' ? '<' : '>';
        $order = $dir === 'up' ? 'DESC' : 'ASC';
        $stmt = $this->pdo()->prepare(
            "SELECT id, position FROM form_questions
             WHERE form_version_id = :v AND position {$op} :p
             ORDER BY position {$order} LIMIT 1"
        );
        $stmt->execute(['v' => $q['form_version_id'], 'p' => $q['position']]);
        $neighbor = $stmt->fetch();
        if (!$neighbor) {
            return;
        }

        $pdo = $this->pdo();
        $pdo->beginTransaction();
        try {
            $u = $pdo->prepare('UPDATE form_questions SET position = :p WHERE id = :id');
            $u->execute(['p' => (int) $neighbor['position'], 'id' => $id]);
            $u->execute(['p' => (int) $q['position'], 'id' => (int) $neighbor['id']]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ----- Options -----

    /** @return array<int, array<string, mixed>> */
    public function optionsFor(int $questionId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM form_question_options WHERE question_id = :q ORDER BY position ASC, id ASC');
        $stmt->execute(['q' => $questionId]);
        return $stmt->fetchAll();
    }

    /** @return array<int, array<int, array<string, mixed>>> options grouped by question_id (no N+1) */
    public function optionsByQuestion(int $versionId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT o.* FROM form_question_options o
             JOIN form_questions q ON q.id = o.question_id
             WHERE q.form_version_id = :v
             ORDER BY o.question_id ASC, o.position ASC, o.id ASC'
        );
        $stmt->execute(['v' => $versionId]);
        $grouped = [];
        foreach ($stmt->fetchAll() as $o) {
            $grouped[(int) $o['question_id']][] = $o;
        }
        return $grouped;
    }

    /** @param array<int, array{key:string, label:string}> $options */
    public function replaceOptions(int $questionId, array $options): void
    {
        $pdo = $this->pdo();
        $del = $pdo->prepare('DELETE FROM form_question_options WHERE question_id = :q');
        $del->execute(['q' => $questionId]);
        $ins = $pdo->prepare('INSERT INTO form_question_options (question_id, option_key, label, position) VALUES (:q, :k, :l, :p)');
        $pos = 1;
        foreach ($options as $o) {
            $ins->execute(['q' => $questionId, 'k' => $o['key'], 'l' => $o['label'], 'p' => $pos++]);
        }
    }
}
