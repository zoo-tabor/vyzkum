<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Sablony transakcnich e-mailu (cesky zdroj). Preklady do ostatnich jazyku drzi
 * tabulka translations (entity_type 'email_template'). Kdyz tabulka jeste
 * neexistuje (migrace za deployem), metody se chovaji jako prazdne - MailTemplateService
 * ma vlastni fallback (DEFAULTS), takze e-maily se posilaji i bez tabulky.
 */
final class EmailTemplateRepository
{
    public const ENTITY = 'email_template';

    private function pdo(): PDO
    {
        return Database::pdo();
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        try {
            return $this->pdo()->query('SELECT id, `key`, subject, body, placeholders FROM email_templates ORDER BY id ASC')->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return array<string, mixed>|null */
    public function find(string $key): ?array
    {
        try {
            $stmt = $this->pdo()->prepare('SELECT id, `key`, subject, body, placeholders FROM email_templates WHERE `key` = :k LIMIT 1');
            $stmt->execute(['k' => $key]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function update(string $key, string $subject, string $body): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE email_templates SET subject = :s, body = :b, updated_at = NOW() WHERE `key` = :k'
        );
        $stmt->execute(['s' => $subject, 'b' => $body, 'k' => $key]);
    }
}
