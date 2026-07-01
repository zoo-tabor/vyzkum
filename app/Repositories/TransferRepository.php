<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class TransferRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    public function create(int $dogId, ?int $fromOwnerId, string $newName, string $newEmail, ?string $newPhone, string $tokenHash, string $expiresAt, ?int $createdBy): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO ownership_transfer_requests
                (dog_id, from_owner_id, new_owner_name, new_owner_email, new_owner_phone, invite_token_hash, expires_at, created_by_user_id)
             VALUES (:d, :fo, :n, :e, :ph, :h, :exp, :by)'
        );
        $stmt->execute([
            'd' => $dogId, 'fo' => $fromOwnerId, 'n' => $newName, 'e' => $newEmail, 'ph' => $newPhone,
            'h' => $tokenHash, 'exp' => $expiresAt, 'by' => $createdBy,
        ]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function findActiveByHash(string $tokenHash): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT r.*, d.name AS dog_name FROM ownership_transfer_requests r
             JOIN dogs d ON d.id = r.dog_id
             WHERE r.invite_token_hash = :h AND r.status = 'pending' AND r.expires_at > NOW() LIMIT 1"
        );
        $stmt->execute(['h' => $tokenHash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markConfirmed(int $id): void
    {
        $stmt = $this->pdo()->prepare("UPDATE ownership_transfer_requests SET status = 'confirmed', confirmed_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    /** @return array<string, mixed>|null cekajici prevod pro psa */
    public function pendingForDog(int $dogId): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT * FROM ownership_transfer_requests WHERE dog_id = :d AND status = 'pending' AND expires_at > NOW() ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['d' => $dogId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
