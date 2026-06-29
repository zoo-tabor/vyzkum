<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class FilesRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    public function create(string $ownerType, int $ownerId, string $original, string $stored, string $mime, int $size, ?int $uploadedBy): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO files (owner_type, owner_id, original_name, stored_name, mime_type, size, storage_disk, uploaded_by_user_id)
             VALUES (:ot, :oid, :orig, :stored, :mime, :size, "local", :by)'
        );
        $stmt->execute([
            'ot' => $ownerType,
            'oid' => $ownerId,
            'orig' => $original,
            'stored' => $stored,
            'mime' => $mime,
            'size' => $size,
            'by' => $uploadedBy,
        ]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM files WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
