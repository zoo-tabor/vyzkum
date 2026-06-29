<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UserRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $stmt = $this->pdo()->prepare('UPDATE users SET password_hash = :h, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['h' => $hash, 'id' => $id]);
    }

    public function touchLastLogin(int $id): void
    {
        $stmt = $this->pdo()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function setTotpSecret(int $id, ?string $secret): void
    {
        $stmt = $this->pdo()->prepare('UPDATE users SET totp_secret = :s, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['s' => $secret, 'id' => $id]);
    }

    /** Create or update an account by email; returns the user id. */
    public function upsert(string $email, string $passwordHash, string $role): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO users (email, password_hash, role, status)
             VALUES (:email, :hash, :role, "active")
             ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash),
                                     role = VALUES(role),
                                     status = "active",
                                     updated_at = NOW()'
        );
        $stmt->execute(['email' => $email, 'hash' => $passwordHash, 'role' => $role]);

        $id = (int) $this->pdo()->lastInsertId();
        if ($id > 0) {
            return $id;
        }

        $lookup = $this->pdo()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $lookup->execute(['email' => $email]);
        return (int) $lookup->fetchColumn();
    }

    /**
     * Ensure a user exists for this e-mail without touching an existing one
     * (never downgrades role or clears a password). Returns the user id.
     */
    public function ensureUser(string $email, string $role = 'owner'): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO users (email, role, status) VALUES (:e, :r, "active")
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
        );
        $stmt->execute(['e' => $email, 'r' => $role]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function listByRole(string $role): array
    {
        $stmt = $this->pdo()->prepare('SELECT id, email, status, last_login_at, password_hash FROM users WHERE role = :r ORDER BY email ASC');
        $stmt->execute(['r' => $role]);
        return $stmt->fetchAll();
    }

    /** @return array<int, int> breed_id, ke kterym ma uzivatel pristup */
    public function breedIdsFor(int $userId): array
    {
        $stmt = $this->pdo()->prepare('SELECT breed_id FROM user_breed_access WHERE user_id = :u');
        $stmt->execute(['u' => $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @param array<int, int> $breedIds nahradi seznam pristupu uzivatele */
    public function setBreedAccess(int $userId, array $breedIds): void
    {
        $pdo = $this->pdo();
        $del = $pdo->prepare('DELETE FROM user_breed_access WHERE user_id = :u');
        $del->execute(['u' => $userId]);
        $ins = $pdo->prepare('INSERT IGNORE INTO user_breed_access (user_id, breed_id, access_level) VALUES (:u, :b, "read")');
        foreach (array_unique($breedIds) as $breedId) {
            $ins->execute(['u' => $userId, 'b' => $breedId]);
        }
    }

    public function countByRole(?string $role = null): int
    {
        if ($role === null) {
            return (int) $this->pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
        }
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
        $stmt->execute(['role' => $role]);
        return (int) $stmt->fetchColumn();
    }
}
