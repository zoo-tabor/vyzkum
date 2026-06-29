<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class FormResponseRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    public function create(int $versionId, int $dogId, ?int $ownerId, ?int $userId, ?string $note): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO form_responses (form_version_id, dog_id, owner_id, submitted_by_user_id, note)
             VALUES (:v, :d, :o, :u, :n)'
        );
        $stmt->execute(['v' => $versionId, 'd' => $dogId, 'o' => $ownerId, 'u' => $userId, 'n' => $note]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $v */
    public function addAnswer(int $responseId, int $questionId, array $v): void
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO form_answers (response_id, question_id, value_text, value_number, value_date, value_json, option_id)
             VALUES (:r, :q, :text, :num, :date, :json, :opt)'
        );
        $stmt->execute([
            'r' => $responseId,
            'q' => $questionId,
            'text' => $v['text'] ?? null,
            'num' => $v['number'] ?? null,
            'date' => $v['date'] ?? null,
            'json' => isset($v['json']) ? json_encode($v['json'], JSON_UNESCAPED_UNICODE) : null,
            'opt' => $v['option_id'] ?? null,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    public function responsesForDog(int $dogId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT r.id, r.submitted_at, r.note, fd.name AS form_name, v.version
             FROM form_responses r
             JOIN form_versions v ON v.id = r.form_version_id
             JOIN form_definitions fd ON fd.id = v.form_definition_id
             WHERE r.dog_id = :d
             ORDER BY r.submitted_at DESC'
        );
        $stmt->execute(['d' => $dogId]);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT r.*, v.version, fd.name AS form_name, dg.name AS dog_name
             FROM form_responses r
             JOIN form_versions v ON v.id = r.form_version_id
             JOIN form_definitions fd ON fd.id = v.form_definition_id
             JOIN dogs dg ON dg.id = r.dog_id
             WHERE r.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function answers(int $responseId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT a.*, q.label, q.type, q.question_key, q.position
             FROM form_answers a
             JOIN form_questions q ON q.id = a.question_id
             WHERE a.response_id = :r
             ORDER BY q.position ASC, q.id ASC'
        );
        $stmt->execute(['r' => $responseId]);
        return $stmt->fetchAll();
    }
}
