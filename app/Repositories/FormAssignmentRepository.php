<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Prirazeni dotazniku psum/majitelum - jeden ukol na psa pri rozeslani dotazniku.
 * Stav: sent -> completed (kdyz majitel dotaznik vyplni).
 */
final class FormAssignmentRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    /**
     * Zalozi jeden ukol pro danou dvojici pes+majitel a vrati jeho id.
     */
    public function create(int $defId, int $versionId, int $dogId, ?int $ownerId, ?string $email, string $emailStatus): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO form_assignments
                (form_definition_id, form_version_id, dog_id, owner_id, recipient_email, status, email_status, sent_at)
             VALUES (:def, :ver, :dog, :owner, :email, "sent", :es, NOW())'
        );
        $stmt->execute([
            'def' => $defId,
            'ver' => $versionId,
            'dog' => $dogId,
            'owner' => $ownerId,
            'email' => $email,
            'es' => $emailStatus,
        ]);
        return (int) $this->pdo()->lastInsertId();
    }

    /**
     * Oznaci nejnovejsi otevreny ukol (pes + dotaznik) jako vyplneny.
     */
    public function markCompleted(int $defId, int $dogId, int $responseId): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE form_assignments
                SET status = "completed", completed_at = NOW(), form_response_id = :r
             WHERE form_definition_id = :def AND dog_id = :dog AND status = "sent"
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['r' => $responseId, 'def' => $defId, 'dog' => $dogId]);
    }

    /** @return array{total:int, completed:int, last_sent:?string} */
    public function statsForDefinition(int $defId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(status = "completed") AS completed,
                    MAX(sent_at) AS last_sent
             FROM form_assignments WHERE form_definition_id = :def'
        );
        $stmt->execute(['def' => $defId]);
        $row = $stmt->fetch() ?: [];
        return [
            'total' => (int) ($row['total'] ?? 0),
            'completed' => (int) ($row['completed'] ?? 0),
            'last_sent' => $row['last_sent'] ?? null,
        ];
    }
}
