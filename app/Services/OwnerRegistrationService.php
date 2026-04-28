<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class OwnerRegistrationService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    /** @param array<string, mixed> $data */
    public function register(array $sample, array $data, ?array $file): void
    {
        $this->pdo->beginTransaction();

        try {
            $ownerId = $this->upsertOwner($data);
            $dogId = $this->createDog((int) $sample['id'], $ownerId, $data);
            $this->createConsent((int) $sample['id'], $dogId, $ownerId, $data);

            if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $this->storePedigree($dogId, $file);
            }

            $stmt = $this->pdo->prepare("
                UPDATE samples
                SET status = 'owner_submitted', owner_submitted_at = NOW(), updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute(['id' => $sample['id']]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function upsertOwner(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO owners (name, email, phone, address, contact_consent, newsletter_consent)
            VALUES (:name, :email, :phone, :address, :contact_consent, :newsletter_consent)
            ON DUPLICATE KEY UPDATE
                name = VALUES(name),
                phone = VALUES(phone),
                address = VALUES(address),
                contact_consent = VALUES(contact_consent),
                newsletter_consent = VALUES(newsletter_consent)
        ");
        $stmt->execute([
            'name' => $data['owner_name'],
            'email' => $data['owner_email'],
            'phone' => $data['owner_phone'] ?: null,
            'address' => $data['owner_address'] ?: null,
            'contact_consent' => !empty($data['future_contact_consent']) ? 1 : 0,
            'newsletter_consent' => !empty($data['newsletter_consent']) ? 1 : 0,
        ]);

        $id = (int) $this->pdo->lastInsertId();
        if ($id > 0) {
            return $id;
        }

        $lookup = $this->pdo->prepare('SELECT id FROM owners WHERE email = :email LIMIT 1');
        $lookup->execute(['email' => $data['owner_email']]);
        return (int) $lookup->fetchColumn();
    }

    private function createDog(int $samplePk, int $ownerId, array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO dogs
                (sample_id, owner_id, chip_number, name, breed, sex, birth_date, pedigree_number, registry, health_status_at_collection, health_note)
            VALUES
                (:sample_id, :owner_id, :chip_number, :name, :breed, :sex, :birth_date, :pedigree_number, :registry, :health_status, :health_note)
        ");
        $stmt->execute([
            'sample_id' => $samplePk,
            'owner_id' => $ownerId,
            'chip_number' => $data['chip_number'],
            'name' => $data['dog_name'],
            'breed' => $data['breed'],
            'sex' => $data['sex'],
            'birth_date' => $data['birth_date'],
            'pedigree_number' => $data['pedigree_number'],
            'registry' => $data['registry'] ?: null,
            'health_status' => $data['health_status'],
            'health_note' => $data['health_note'] ?: null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function createConsent(int $samplePk, int $dogId, int $ownerId, array $data): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO consents
                (sample_id, dog_id, owner_id, consent_version, research_consent, gdpr_consent,
                 future_contact_consent, results_consent, owner_name_at_consent, ip_address)
            VALUES
                (:sample_id, :dog_id, :owner_id, :consent_version, 1, 1,
                 :future_contact_consent, :results_consent, :owner_name, :ip)
        ");
        $stmt->execute([
            'sample_id' => $samplePk,
            'dog_id' => $dogId,
            'owner_id' => $ownerId,
            'consent_version' => '2026-01',
            'future_contact_consent' => !empty($data['future_contact_consent']) ? 1 : 0,
            'results_consent' => !empty($data['results_consent']) ? 1 : 0,
            'owner_name' => $data['owner_name'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    private function storePedigree(int $dogId, array $file): void
    {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        $mime = mime_content_type((string) $file['tmp_name']) ?: '';
        if (!in_array($mime, $allowed, true)) {
            throw new \RuntimeException('Nepodporovany format rodokmenu.');
        }

        if ((int) $file['size'] > 10 * 1024 * 1024) {
            throw new \RuntimeException('Soubor rodokmenu je prilis velky.');
        }

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            default => 'bin',
        };
        $storedName = 'pedigree_' . $dogId . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
        $target = STORAGE_PATH . '/uploads/' . $storedName;

        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            throw new \RuntimeException('Rodokmen se nepodarilo ulozit.');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO pedigree_documents (dog_id, original_name, stored_name, mime_type, file_size)
            VALUES (:dog_id, :original_name, :stored_name, :mime_type, :file_size)
        ");
        $stmt->execute([
            'dog_id' => $dogId,
            'original_name' => basename((string) $file['name']),
            'stored_name' => $storedName,
            'mime_type' => $mime,
            'file_size' => (int) $file['size'],
        ]);
    }
}
