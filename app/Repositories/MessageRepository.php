<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class MessageRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    public function findOrCreateDogThread(int $dogId, ?int $userId): int
    {
        $existing = $this->dogThread($dogId);
        if ($existing !== null) {
            return (int) $existing['id'];
        }
        $stmt = $this->pdo()->prepare(
            "INSERT INTO message_threads (entity_type, entity_id, subject, status, created_by_user_id)
             VALUES ('dog', :d, NULL, 'open', :u)"
        );
        $stmt->execute(['d' => $dogId, 'u' => $userId]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function dogThread(int $dogId): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT * FROM message_threads WHERE entity_type = 'dog' AND entity_id = :d ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['d' => $dogId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Obecne (nikoliv ke psovi) vlakno majitele. */
    public function findOrCreateOwnerThread(int $ownerId, ?int $userId): int
    {
        $existing = $this->ownerThread($ownerId);
        if ($existing !== null) {
            return (int) $existing['id'];
        }
        $stmt = $this->pdo()->prepare(
            "INSERT INTO message_threads (entity_type, entity_id, subject, status, created_by_user_id)
             VALUES ('owner', :o, NULL, 'open', :u)"
        );
        $stmt->execute(['o' => $ownerId, 'u' => $userId]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function ownerThread(int $ownerId): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT * FROM message_threads WHERE entity_type = 'owner' AND entity_id = :o ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute(['o' => $ownerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function addMessage(int $threadId, ?int $userId, ?string $role, string $body, ?string $newStatus = null): void
    {
        $ins = $this->pdo()->prepare(
            'INSERT INTO messages (thread_id, sender_user_id, sender_role, body) VALUES (:t, :u, :r, :b)'
        );
        $ins->execute(['t' => $threadId, 'u' => $userId, 'r' => $role, 'b' => $body]);

        if ($newStatus !== null) {
            $upd = $this->pdo()->prepare('UPDATE message_threads SET last_message_at = NOW(), status = :s WHERE id = :id');
            $upd->execute(['s' => $newStatus, 'id' => $threadId]);
        } else {
            $this->pdo()->prepare('UPDATE message_threads SET last_message_at = NOW() WHERE id = :id')->execute(['id' => $threadId]);
        }
    }

    public function countMessages(int $threadId): int
    {
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM messages WHERE thread_id = :t');
        $stmt->execute(['t' => $threadId]);
        return (int) $stmt->fetchColumn();
    }

    /** Oznaci vlakno jako precteny danym uzivatelem (k aktualnimu casu). */
    public function markRead(int $threadId, int $userId): void
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO message_reads (thread_id, user_id, last_read_at) VALUES (:t, :u, NOW())
             ON DUPLICATE KEY UPDATE last_read_at = NOW()'
        );
        $stmt->execute(['t' => $threadId, 'u' => $userId]);
    }

    /** Ma vlakno pro daneho uzivatele neprectenou zpravu od nekoho jineho? */
    public function hasUnseenForUser(int $threadId, int $userId): bool
    {
        $stmt = $this->pdo()->prepare(
            "SELECT 1 FROM messages m
             WHERE m.thread_id = :t
               AND (m.sender_user_id IS NULL OR m.sender_user_id <> :u)
               AND m.created_at > COALESCE(
                   (SELECT r.last_read_at FROM message_reads r WHERE r.thread_id = :t2 AND r.user_id = :u2),
                   '1000-01-01 00:00:00')
             LIMIT 1"
        );
        $stmt->execute(['t' => $threadId, 'u' => $userId, 't2' => $threadId, 'u2' => $userId]);
        return (bool) $stmt->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> */
    public function messages(int $threadId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT m.*, u.email AS sender_email FROM messages m
             LEFT JOIN users u ON u.id = m.sender_user_id
             WHERE m.thread_id = :t ORDER BY m.created_at ASC, m.id ASC'
        );
        $stmt->execute(['t' => $threadId]);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function thread(int $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT t.*, d.name AS dog_name, o.display_name AS owner_name FROM message_threads t
             LEFT JOIN dogs d ON d.id = t.entity_id AND t.entity_type = 'dog'
             LEFT JOIN owners o ON o.id = t.entity_id AND t.entity_type = 'owner'
             WHERE t.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function threadsList(string $status = ''): array
    {
        $where = $status !== '' ? 'WHERE t.status = :status' : '';
        $sql =
            "SELECT t.id, t.status, t.last_message_at, t.entity_type, t.entity_id,
                    d.name AS dog_name,
                    CASE WHEN t.entity_type = 'owner' THEN t.entity_id ELSE dogown.owner_id END AS owner_id,
                    CASE WHEN t.entity_type = 'owner' THEN oo.display_name ELSE od.display_name END AS owner_name,
                    (SELECT COUNT(*) FROM messages m WHERE m.thread_id = t.id) AS msg_count
             FROM message_threads t
             LEFT JOIN dogs d ON d.id = t.entity_id AND t.entity_type = 'dog'
             LEFT JOIN dog_owners dogown ON dogown.dog_id = t.entity_id AND t.entity_type = 'dog' AND dogown.is_current = 1
             LEFT JOIN owners od ON od.id = dogown.owner_id
             LEFT JOIN owners oo ON oo.id = t.entity_id AND t.entity_type = 'owner'
             {$where}
             ORDER BY owner_name ASC, t.last_message_at DESC LIMIT 300";
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($status !== '' ? ['status' => $status] : []);
        return $stmt->fetchAll();
    }

    /** Pocet vlaken ve stavu 'open' (upozorneni pro admina). */
    public function countOpenThreads(): int
    {
        return (int) $this->pdo()->query("SELECT COUNT(*) FROM message_threads WHERE status = 'open'")->fetchColumn();
    }

    /** Pocet neprectenych zprav majitele (podle jeho user_id) - obecne vlakno + vlakna aktualnich psu. */
    public function countUnreadForOwnerUser(int $userId): int
    {
        $stmt = $this->pdo()->prepare(
            "SELECT COUNT(*)
             FROM messages m
             JOIN message_threads t ON t.id = m.thread_id
             JOIN owners o ON o.user_id = :u1
             LEFT JOIN message_reads r ON r.thread_id = t.id AND r.user_id = :u2
             WHERE (
                    (t.entity_type = 'owner' AND t.entity_id = o.id)
                 OR (t.entity_type = 'dog' AND t.entity_id IN (
                        SELECT d2.dog_id FROM dog_owners d2 WHERE d2.owner_id = o.id AND d2.is_current = 1))
                 )
               AND (m.sender_user_id IS NULL OR m.sender_user_id <> :u3)
               AND m.created_at > COALESCE(r.last_read_at, '1000-01-01 00:00:00')"
        );
        $stmt->execute(['u1' => $userId, 'u2' => $userId, 'u3' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function setStatus(int $threadId, string $status): void
    {
        $stmt = $this->pdo()->prepare('UPDATE message_threads SET status = :s WHERE id = :id');
        $stmt->execute(['s' => $status, 'id' => $threadId]);
    }
}
