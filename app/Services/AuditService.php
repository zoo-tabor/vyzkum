<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class AuditService
{
    /**
     * Record a state change for the audit trail.
     *
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     */
    public static function log(
        ?int $actorUserId,
        ?string $actorRole,
        string $action,
        string $entityType,
        ?string $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO audit_logs
                (actor_user_id, actor_role, action, entity_type, entity_id,
                 old_values_json, new_values_json, ip_address)
             VALUES
                (:actor_user_id, :actor_role, :action, :entity_type, :entity_id,
                 :old_json, :new_json, :ip)'
        );

        $stmt->execute([
            'actor_user_id' => $actorUserId,
            'actor_role' => $actorRole,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_json' => $oldValues !== null ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
            'new_json' => $newValues !== null ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
