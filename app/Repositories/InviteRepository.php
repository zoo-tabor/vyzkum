<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class InviteRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    public function create(?int $userId, ?int $ownerId, string $tokenHash, string $purpose, string $expiresAt, ?int $createdBy): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO password_invites
                (user_id, owner_id, token_hash, purpose, expires_at, sent_at, created_by_user_id)
             VALUES (:u, :o, :h, :p, :exp, NOW(), :by)'
        );
        $stmt->execute([
            'u' => $userId,
            'o' => $ownerId,
            'h' => $tokenHash,
            'p' => $purpose,
            'exp' => $expiresAt,
            'by' => $createdBy,
        ]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** @return array<string, mixed>|null active (unused, not expired) invite */
    public function findActiveByHash(string $tokenHash): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM password_invites
             WHERE token_hash = :h AND used_at IS NULL AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute(['h' => $tokenHash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markUsed(int $id): void
    {
        $stmt = $this->pdo()->prepare('UPDATE password_invites SET used_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** @return array<string, mixed>|null latest invite for a user (for button state) */
    public function latestForUser(int $userId): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM password_invites WHERE user_id = :u ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['u' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
