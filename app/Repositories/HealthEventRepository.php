<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class HealthEventRepository
{
    public const TYPES = ['disease', 'examination', 'castration', 'death', 'other'];

    private function pdo(): PDO
    {
        return Database::pdo();
    }

    /** @param array<string, mixed>|null $valueJson */
    public function create(
        int $dogId,
        ?int $breedId,
        string $eventType,
        ?string $eventDate,
        string $sourceType,
        ?int $sourceId,
        ?string $normalizedCode,
        ?array $valueJson,
        ?string $note,
        ?int $createdBy,
        ?string $eventEndDate = null
    ): int {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO health_events
                (dog_id, breed_id, event_type, event_date, event_end_date, source_type, source_id, normalized_code, value_json, note, created_by_user_id)
             VALUES (:d, :b, :t, :date, :end, :st, :sid, :code, :json, :note, :by)'
        );
        $stmt->execute([
            'd' => $dogId,
            'b' => $breedId,
            't' => $eventType,
            'date' => $eventDate ?: null,
            'end' => $eventEndDate ?: null,
            'st' => $sourceType,
            'sid' => $sourceId,
            'code' => $normalizedCode,
            'json' => $valueJson !== null ? json_encode($valueJson, JSON_UNESCAPED_UNICODE) : null,
            'note' => $note,
            'by' => $createdBy,
        ]);
        return (int) $this->pdo()->lastInsertId();
    }

    /** @return array<int, array<string, mixed>> */
    public function byDog(int $dogId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM health_events WHERE dog_id = :d ORDER BY event_date DESC, id DESC'
        );
        $stmt->execute(['d' => $dogId]);
        return $stmt->fetchAll();
    }

    /** @return array<int, array{event_type:string, c:int}> */
    public function frequencyByType(int $breedId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT event_type, COUNT(*) AS c FROM health_events WHERE breed_id = :b GROUP BY event_type ORDER BY c DESC'
        );
        $stmt->execute(['b' => $breedId]);
        return $stmt->fetchAll();
    }

    /** @return array<int, array{normalized_code:string, c:int}> rozpad podle kodu pro dany typ */
    public function frequencyByCode(int $breedId, string $type): array
    {
        $stmt = $this->pdo()->prepare(
            "SELECT COALESCE(NULLIF(normalized_code, ''), '(neuvedeno)') AS normalized_code, COUNT(*) AS c
             FROM health_events WHERE breed_id = :b AND event_type = :t
             GROUP BY normalized_code ORDER BY c DESC LIMIT 30"
        );
        $stmt->execute(['b' => $breedId, 't' => $type]);
        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function recentForBreed(int $breedId, int $limit = 100): array
    {
        $limit = max(1, $limit);
        $stmt = $this->pdo()->prepare(
            "SELECT h.*, d.name AS dog_name FROM health_events h
             JOIN dogs d ON d.id = h.dog_id
             WHERE h.breed_id = :b ORDER BY h.created_at DESC LIMIT {$limit}"
        );
        $stmt->execute(['b' => $breedId]);
        return $stmt->fetchAll();
    }
}
