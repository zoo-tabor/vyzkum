<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class SampleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /** @return array<string, mixed>|null */
    public function findForToken(string $sampleId, string $token, string $role): ?array
    {
        $column = $role === 'vet' ? 'vet_token_hash' : 'owner_token_hash';
        $stmt = $this->pdo->prepare("
            SELECT s.*, v.name AS vet_name, v.clinic_name
            FROM samples s
            LEFT JOIN vets v ON v.id = s.vet_id
            WHERE s.sample_id = :sample_id AND s.{$column} = :token_hash
            LIMIT 1
        ");
        $stmt->execute([
            'sample_id' => $sampleId,
            'token_hash' => hash('sha256', $token),
        ]);

        $sample = $stmt->fetch();
        return $sample ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query("
            SELECT s.sample_id, s.status, s.chip_number_vet, s.collection_date, s.sample_type,
                   d.name AS dog_name, d.breed, o.email AS owner_email
            FROM samples s
            LEFT JOIN dogs d ON d.sample_id = s.id
            LEFT JOIN owners o ON o.id = d.owner_id
            ORDER BY s.created_at DESC
            LIMIT 300
        ")->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function detail(string $sampleId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, v.name AS vet_name, v.clinic_name,
                   d.id AS dog_id, d.name AS dog_name, d.breed, d.sex, d.birth_date,
                   d.chip_number AS dog_chip_number, d.pedigree_number, d.registry,
                   d.health_status_at_collection, d.health_note, d.death_date, d.death_cause,
                   o.name AS owner_name, o.email AS owner_email, o.phone AS owner_phone,
                   o.contact_consent, o.newsletter_consent
            FROM samples s
            LEFT JOIN vets v ON v.id = s.vet_id
            LEFT JOIN dogs d ON d.sample_id = s.id
            LEFT JOIN owners o ON o.id = d.owner_id
            WHERE s.sample_id = :sample_id
            LIMIT 1
        ");
        $stmt->execute(['sample_id' => $sampleId]);

        $sample = $stmt->fetch();
        return $sample ?: null;
    }

    public function submitVet(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE samples
            SET status = 'vet_submitted',
                chip_number_vet = :chip,
                sample_type = :sample_type,
                sample_type_other = :sample_type_other,
                material_count = :material_count,
                collection_date = :collection_date,
                vet_submitted_at = NOW(),
                updated_at = NOW()
            WHERE id = :id AND vet_submitted_at IS NULL
        ");
        $stmt->execute([
            'id' => $id,
            'chip' => $data['chip_number_vet'],
            'sample_type' => $data['sample_type'],
            'sample_type_other' => $data['sample_type_other'] ?: null,
            'material_count' => $data['material_count'],
            'collection_date' => $data['collection_date'],
        ]);
    }

    public function updateStatus(string $sampleId, string $status): void
    {
        $stmt = $this->pdo->prepare("UPDATE samples SET status = :status, updated_at = NOW() WHERE sample_id = :sample_id");
        $stmt->execute(['status' => $status, 'sample_id' => $sampleId]);
    }

    /** @return array<int, array{sample_id:string, vet_url:string, owner_url:string}> */
    public function createBatch(int $count, ?int $vetId, string $appUrl): array
    {
        $insert = $this->pdo->prepare("
            INSERT INTO samples (sample_id, vet_id, status, vet_token_hash, owner_token_hash)
            VALUES (:sample_id, :vet_id, :status, :vet_token_hash, :owner_token_hash)
        ");

        $rows = [];
        $year = date('Y');
        $appUrl = rtrim($appUrl, '/');

        for ($i = 0; $i < $count; $i++) {
            $created = null;

            for ($attempt = 0; $attempt < 10; $attempt++) {
                $candidate = 'SMP-' . $year . '-' . strtoupper(bin2hex(random_bytes(4)));
                $vetToken = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
                $ownerToken = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');

                try {
                    $insert->execute([
                        'sample_id' => $candidate,
                        'vet_id' => $vetId,
                        'status' => $vetId ? 'assigned_to_vet' : 'created',
                        'vet_token_hash' => hash('sha256', $vetToken),
                        'owner_token_hash' => hash('sha256', $ownerToken),
                    ]);

                    $created = [
                        'sample_id' => $candidate,
                        'vet_url' => $appUrl . '/vet/' . rawurlencode($candidate) . '/' . rawurlencode($vetToken),
                        'owner_url' => $appUrl . '/dog/' . rawurlencode($candidate) . '/' . rawurlencode($ownerToken),
                    ];
                    break;
                } catch (\PDOException $e) {
                    if ($e->getCode() !== '23000') {
                        throw $e;
                    }
                }
            }

            if ($created === null) {
                throw new \RuntimeException('Nepodarilo se vytvorit unikatni sample_id.');
            }

            $rows[] = $created;
        }

        return $rows;
    }
}
