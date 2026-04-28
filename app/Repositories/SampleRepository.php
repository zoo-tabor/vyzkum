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
                   o.name AS owner_name, o.email AS owner_email, o.phone AS owner_phone, o.address AS owner_address,
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

    /** @return array{batch:array<string, mixed>, rows:array<int, array<string, string>>} */
    public function createBatch(int $count, ?int $vetId, string $appUrl, ?string $label = null): array
    {
        $batchInsert = $this->pdo->prepare("
            INSERT INTO sample_batches (vet_id, label, sample_count)
            VALUES (:vet_id, :label, :sample_count)
        ");
        $sampleInsert = $this->pdo->prepare("
            INSERT INTO samples (
                sample_id, batch_id, batch_sequence, vet_id, status,
                vet_token_hash, owner_token_hash, vet_token, owner_token
            )
            VALUES (
                :sample_id, :batch_id, :batch_sequence, :vet_id, :status,
                :vet_token_hash, :owner_token_hash, :vet_token, :owner_token
            )
        ");

        $rows = [];
        $year = date('Y');
        $appUrl = rtrim($appUrl, '/');

        $this->pdo->beginTransaction();
        try {
            $batchInsert->execute([
                'vet_id' => $vetId,
                'label' => self::nullable($label),
                'sample_count' => $count,
            ]);
            $batchId = (int) $this->pdo->lastInsertId();

            for ($i = 0; $i < $count; $i++) {
                $created = null;

                for ($attempt = 0; $attempt < 10; $attempt++) {
                    $candidate = 'SMP-' . $year . '-' . strtoupper(bin2hex(random_bytes(4)));
                    $vetToken = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
                    $ownerToken = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');

                    try {
                        $sampleInsert->execute([
                            'sample_id' => $candidate,
                            'batch_id' => $batchId,
                            'batch_sequence' => $i + 1,
                            'vet_id' => $vetId,
                            'status' => $vetId ? 'assigned_to_vet' : 'created',
                            'vet_token_hash' => hash('sha256', $vetToken),
                            'owner_token_hash' => hash('sha256', $ownerToken),
                            'vet_token' => $vetToken,
                            'owner_token' => $ownerToken,
                        ]);

                        $created = [
                            'sample_id' => $candidate,
                            'vet_url' => $this->vetUrl($appUrl, $candidate, $vetToken),
                            'owner_url' => $this->ownerUrl($appUrl, $candidate, $ownerToken),
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

            $this->pdo->commit();

            return [
                'batch' => ['id' => $batchId, 'label' => self::nullable($label), 'sample_count' => $count],
                'rows' => $rows,
            ];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function batches(): array
    {
        return $this->pdo->query("
            SELECT b.id, b.label, b.sample_count, b.created_at, v.name AS vet_name, v.clinic_name
            FROM sample_batches b
            LEFT JOIN vets v ON v.id = b.vet_id
            ORDER BY b.created_at DESC, b.id DESC
            LIMIT 200
        ")->fetchAll();
    }

    /** @return array{batch:array<string, mixed>, rows:array<int, array<string, ?string>>}|null */
    public function batchLabels(int $batchId, string $appUrl): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT b.id, b.label, b.sample_count, b.created_at, v.name AS vet_name, v.clinic_name
            FROM sample_batches b
            LEFT JOIN vets v ON v.id = b.vet_id
            WHERE b.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $batchId]);
        $batch = $stmt->fetch();
        if (!$batch) {
            return null;
        }

        $sampleStmt = $this->pdo->prepare("
            SELECT sample_id, vet_token, owner_token
            FROM samples
            WHERE batch_id = :batch_id
            ORDER BY batch_sequence ASC, id ASC
        ");
        $sampleStmt->execute(['batch_id' => $batchId]);

        $appUrl = rtrim($appUrl, '/');
        $rows = [];
        foreach ($sampleStmt->fetchAll() as $sample) {
            $rows[] = [
                'sample_id' => $sample['sample_id'],
                'vet_url' => $sample['vet_token'] ? $this->vetUrl($appUrl, $sample['sample_id'], $sample['vet_token']) : null,
                'owner_url' => $sample['owner_token'] ? $this->ownerUrl($appUrl, $sample['sample_id'], $sample['owner_token']) : null,
            ];
        }

        return ['batch' => $batch, 'rows' => $rows];
    }

    private function vetUrl(string $appUrl, string $sampleId, string $token): string
    {
        return $appUrl . '/vet/' . rawurlencode($sampleId) . '/' . rawurlencode($token);
    }

    private function ownerUrl(string $appUrl, string $sampleId, string $token): string
    {
        return $appUrl . '/dog/' . rawurlencode($sampleId) . '/' . rawurlencode($token);
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
