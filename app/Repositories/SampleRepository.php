<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Support\SampleCode;
use PDO;

final class SampleRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    /**
     * Vygeneruje davku vzorku: sample_id, hashovane + (pro reprint) viditelne tokeny,
     * tokenizovane odkazy. Port z old_app, napojeny na breed_id.
     *
     * @return array{batch: array<string, mixed>, rows: array<int, array<string, mixed>>}
     */
    public function createBatch(int $count, ?int $breedId, ?int $vetId, ?string $label, string $appUrl, ?int $userId): array
    {
        $appUrl = rtrim($appUrl, '/');
        $pdo = $this->pdo();

        $batchInsert = $pdo->prepare(
            'INSERT INTO sample_batches (label, breed_id, vet_id, sample_count, created_by_user_id)
             VALUES (:label, :breed, :vet, :count, :by)'
        );
        $sampleInsert = $pdo->prepare(
            'INSERT INTO samples
                (sample_id, batch_id, batch_sequence, breed_id, vet_id, status,
                 vet_token_hash, owner_token_hash, vet_token, owner_token)
             VALUES
                (:sample_id, :batch_id, :seq, :breed, :vet, :status,
                 :vet_hash, :owner_hash, :vet_token, :owner_token)'
        );

        $rows = [];
        $pdo->beginTransaction();
        try {
            $batchInsert->execute([
                'label' => self::nv($label),
                'breed' => $breedId,
                'vet' => $vetId,
                'count' => $count,
                'by' => $userId,
            ]);
            $batchId = (int) $pdo->lastInsertId();

            for ($i = 0; $i < $count; $i++) {
                $created = null;
                for ($attempt = 0; $attempt < 10; $attempt++) {
                    $candidate = SampleCode::sampleId();
                    $vetToken = SampleCode::token();
                    $ownerToken = SampleCode::token();
                    try {
                        $sampleInsert->execute([
                            'sample_id' => $candidate,
                            'batch_id' => $batchId,
                            'seq' => $i + 1,
                            'breed' => $breedId,
                            'vet' => $vetId,
                            'status' => $vetId ? 'assigned_to_vet' : 'created',
                            'vet_hash' => hash('sha256', $vetToken),
                            'owner_hash' => hash('sha256', $ownerToken),
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
                    throw new \RuntimeException('Nepodarilo se vygenerovat unikatni sample_id.');
                }
                $rows[] = $created;
            }

            $pdo->commit();
            return ['batch' => ['id' => $batchId, 'label' => self::nv($label), 'sample_count' => $count], 'rows' => $rows];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** @return array<int, array<string, mixed>> */
    public function batches(): array
    {
        return $this->pdo()->query(
            'SELECT b.id, b.label, b.sample_count, b.created_at,
                    v.name AS vet_name, v.clinic_name, br.name AS breed_name
             FROM sample_batches b
             LEFT JOIN vets v ON v.id = b.vet_id
             LEFT JOIN breeds br ON br.id = b.breed_id
             ORDER BY b.created_at DESC, b.id DESC
             LIMIT 200'
        )->fetchAll();
    }

    /** @return array{batch: array<string, mixed>, rows: array<int, array<string, ?string>>}|null */
    public function batchLabels(int $batchId, string $appUrl): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT b.id, b.label, b.sample_count, b.created_at, v.name AS vet_name, br.name AS breed_name
             FROM sample_batches b
             LEFT JOIN vets v ON v.id = b.vet_id
             LEFT JOIN breeds br ON br.id = b.breed_id
             WHERE b.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $batchId]);
        $batch = $stmt->fetch();
        if (!$batch) {
            return null;
        }

        $sampleStmt = $this->pdo()->prepare(
            'SELECT sample_id, vet_token, owner_token FROM samples WHERE batch_id = :b ORDER BY batch_sequence ASC, id ASC'
        );
        $sampleStmt->execute(['b' => $batchId]);

        $appUrl = rtrim($appUrl, '/');
        $rows = [];
        foreach ($sampleStmt->fetchAll() as $s) {
            $rows[] = [
                'sample_id' => $s['sample_id'],
                'vet_url' => $s['vet_token'] ? $this->vetUrl($appUrl, $s['sample_id'], $s['vet_token']) : null,
                'owner_url' => $s['owner_token'] ? $this->ownerUrl($appUrl, $s['sample_id'], $s['owner_token']) : null,
            ];
        }
        return ['batch' => $batch, 'rows' => $rows];
    }

    /** @return array<int, array<string, mixed>> */
    public function listForBreed(?int $breedId, string $status = '', int $limit = 300): array
    {
        $where = [];
        $params = [];
        if ($breedId !== null) {
            $where[] = 's.breed_id = :breed';
            $params['breed'] = $breedId;
        }
        if ($status !== '') {
            $where[] = 's.status = :status';
            $params['status'] = $status;
        }
        $sql =
            'SELECT s.sample_id, s.status, s.collection_date, s.sample_type, s.created_at,
                    br.name AS breed_name, d.name AS dog_name, v.name AS vet_name
             FROM samples s
             LEFT JOIN breeds br ON br.id = s.breed_id
             LEFT JOIN dogs d ON d.id = s.dog_id
             LEFT JOIN vets v ON v.id = s.vet_id'
            . ($where !== [] ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY s.created_at DESC LIMIT ' . max(1, $limit);
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function detail(string $sampleId): ?array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT s.*, br.name AS breed_name, v.name AS vet_name, v.clinic_name,
                    d.id AS dog_id, d.name AS dog_name
             FROM samples s
             LEFT JOIN breeds br ON br.id = s.breed_id
             LEFT JOIN vets v ON v.id = s.vet_id
             LEFT JOIN dogs d ON d.id = s.dog_id
             WHERE s.sample_id = :sid LIMIT 1'
        );
        $stmt->execute(['sid' => $sampleId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateStatus(string $sampleId, string $status): void
    {
        $stmt = $this->pdo()->prepare('UPDATE samples SET status = :s, updated_at = NOW() WHERE sample_id = :sid');
        $stmt->execute(['s' => $status, 'sid' => $sampleId]);
    }

    /** @return array<string, mixed>|null */
    public function findForToken(string $sampleId, string $token, string $role): ?array
    {
        $column = $role === 'vet' ? 'vet_token_hash' : 'owner_token_hash';
        $stmt = $this->pdo()->prepare(
            "SELECT s.*, v.name AS vet_name, v.clinic_name, br.name AS breed_name, br.slug AS breed_slug
             FROM samples s
             LEFT JOIN vets v ON v.id = s.vet_id
             LEFT JOIN breeds br ON br.id = s.breed_id
             WHERE s.sample_id = :sid AND s.{$column} = :hash LIMIT 1"
        );
        $stmt->execute(['sid' => $sampleId, 'hash' => hash('sha256', $token)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function submitVet(int $id, array $data): void
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE samples
             SET status = 'vet_submitted', chip_number_vet = :chip, sample_type = :type,
                 sample_type_other = :other, material_count = :count, collection_date = :date,
                 vet_submitted_at = NOW(), updated_at = NOW()
             WHERE id = :id AND vet_submitted_at IS NULL"
        );
        $stmt->execute([
            'id' => $id,
            'chip' => $data['chip_number_vet'],
            'type' => $data['sample_type'],
            'other' => $data['sample_type_other'] ?: null,
            'count' => $data['material_count'],
            'date' => $data['collection_date'],
        ]);
    }

    public function attachDog(int $sampleId, int $dogId): void
    {
        $stmt = $this->pdo()->prepare(
            "UPDATE samples SET dog_id = :d, status = 'owner_submitted', owner_submitted_at = NOW(), updated_at = NOW()
             WHERE id = :id"
        );
        $stmt->execute(['d' => $dogId, 'id' => $sampleId]);
    }

    private function vetUrl(string $appUrl, string $sampleId, string $token): string
    {
        return $appUrl . '/vet/' . rawurlencode($sampleId) . '/' . rawurlencode($token);
    }

    private function ownerUrl(string $appUrl, string $sampleId, string $token): string
    {
        return $appUrl . '/dog/' . rawurlencode($sampleId) . '/' . rawurlencode($token);
    }

    private static function nv(?string $v): ?string
    {
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }
}
