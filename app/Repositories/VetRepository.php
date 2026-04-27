<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class VetRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query("
            SELECT id, name, clinic_name, email, phone, address, created_at
            FROM vets
            ORDER BY name ASC, clinic_name ASC
        ")->fetchAll();
    }

    public function create(array $data): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO vets (name, clinic_name, email, phone, address)
            VALUES (:name, :clinic_name, :email, :phone, :address)
        ");
        $stmt->execute([
            'name' => trim((string) $data['name']),
            'clinic_name' => self::nullable($data['clinic_name'] ?? null),
            'email' => self::nullable($data['email'] ?? null),
            'phone' => self::nullable($data['phone'] ?? null),
            'address' => self::nullable($data['address'] ?? null),
        ]);
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
