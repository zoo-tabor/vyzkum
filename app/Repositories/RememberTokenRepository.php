<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Tokeny trvaleho prihlaseni. V DB jen hash. Kdyz tabulka jeste neexistuje
 * (migrace za deployem), metody se chovaji jako no-op / prazdne - remember-me
 * proste nefunguje, dokud se nespusti ensure_schema.sql.
 */
final class RememberTokenRepository
{
    /** @var bool|null */
    private static ?bool $enabled = null;

    private function pdo(): PDO
    {
        return Database::pdo();
    }

    private function enabled(): bool
    {
        if (self::$enabled === null) {
            try {
                $this->pdo()->query('SELECT 1 FROM remember_tokens LIMIT 1');
                self::$enabled = true;
            } catch (\Throwable $e) {
                self::$enabled = false;
            }
        }
        return self::$enabled;
    }

    public function create(int $userId, string $tokenHash, string $expiresAt): void
    {
        if (!$this->enabled()) {
            return;
        }
        $stmt = $this->pdo()->prepare(
            'INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (:u, :h, :e)'
        );
        $stmt->execute(['u' => $userId, 'h' => $tokenHash, 'e' => $expiresAt]);
    }

    /** @return array<string, mixed>|null platny (neprosly) token dle hashe */
    public function findValid(string $tokenHash): ?array
    {
        if (!$this->enabled()) {
            return null;
        }
        $stmt = $this->pdo()->prepare(
            'SELECT id, user_id FROM remember_tokens WHERE token_hash = :h AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute(['h' => $tokenHash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function delete(int $id): void
    {
        if (!$this->enabled()) {
            return;
        }
        $this->pdo()->prepare('DELETE FROM remember_tokens WHERE id = :id')->execute(['id' => $id]);
    }

    public function deleteByHash(string $tokenHash): void
    {
        if (!$this->enabled()) {
            return;
        }
        $this->pdo()->prepare('DELETE FROM remember_tokens WHERE token_hash = :h')->execute(['h' => $tokenHash]);
    }

    /** Zrusi vsechny tokeny uzivatele (napr. pri zmene hesla). */
    public function deleteForUser(int $userId): void
    {
        if (!$this->enabled()) {
            return;
        }
        $this->pdo()->prepare('DELETE FROM remember_tokens WHERE user_id = :u')->execute(['u' => $userId]);
    }
}
