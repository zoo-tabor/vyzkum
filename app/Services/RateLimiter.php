<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Simple DB-backed fixed-window rate limiter, used for login / invite endpoints.
 */
final class RateLimiter
{
    public static function tooMany(string $key, int $max, int $decaySeconds): bool
    {
        self::purgeExpired();
        $stmt = Database::pdo()->prepare(
            'SELECT attempts FROM login_throttle WHERE throttle_key = :k AND expires_at > NOW() LIMIT 1'
        );
        $stmt->execute(['k' => self::normalize($key)]);
        $attempts = (int) ($stmt->fetchColumn() ?: 0);
        return $attempts >= $max;
    }

    public static function hit(string $key, int $decaySeconds): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO login_throttle (throttle_key, attempts, expires_at)
             VALUES (:k, 1, DATE_ADD(NOW(), INTERVAL :ttl SECOND))
             ON DUPLICATE KEY UPDATE
                attempts = IF(expires_at > NOW(), attempts + 1, 1),
                expires_at = IF(expires_at > NOW(), expires_at, DATE_ADD(NOW(), INTERVAL :ttl2 SECOND))'
        );
        $stmt->execute(['k' => self::normalize($key), 'ttl' => $decaySeconds, 'ttl2' => $decaySeconds]);
    }

    public static function clear(string $key): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM login_throttle WHERE throttle_key = :k');
        $stmt->execute(['k' => self::normalize($key)]);
    }

    private static function purgeExpired(): void
    {
        Database::pdo()->exec('DELETE FROM login_throttle WHERE expires_at <= NOW()');
    }

    private static function normalize(string $key): string
    {
        return substr(strtolower(trim($key)), 0, 190);
    }
}
