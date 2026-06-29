<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\DogRepository;
use App\Repositories\FilesRepository;
use App\Repositories\OwnerRepository;
use App\Repositories\SampleRepository;
use App\Support\FileStorage;

/**
 * Registrace psa majitelem pres QR: zalozi/najde majitele, psa, vazbu, souhlas,
 * rodokmen (souborovy system) a napoji na vzorek. Po commitu posle pozvanku pro
 * nastaveni hesla (pristup do portalu).
 */
final class OwnerQrRegistrationService
{
    private const CONSENT_VERSION = '2026-01';

    /**
     * @param array<string, mixed> $sample
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $file
     * @return array{owner_id: int, dog_id: int}
     */
    public function register(array $sample, array $data, ?array $file, int $breedId, string $breedSlug): array
    {
        $pdo = Database::pdo();
        $owners = new OwnerRepository();
        $dogs = new DogRepository();
        $files = new FilesRepository();

        $email = strtolower(trim((string) $data['owner_email']));

        $pdo->beginTransaction();
        try {
            $ownerId = $owners->findByPrimaryEmail($email);
            if ($ownerId === null) {
                $ownerId = $owners->create([
                    'display_name' => trim((string) $data['owner_name']),
                    'address' => trim((string) ($data['owner_address'] ?? '')) ?: null,
                    'preferred_contact_method' => 'email',
                    'contact_consent' => !empty($data['future_contact_consent']),
                ]);
                $owners->addEmail($ownerId, $email, true);
                $phone = trim((string) ($data['owner_phone'] ?? ''));
                if ($phone !== '') {
                    $owners->addPhone($ownerId, $phone, null, false);
                }
            }

            $dogId = $dogs->create([
                'breed_id' => $breedId,
                'name' => trim((string) $data['dog_name']),
                'chip_number' => trim((string) ($data['chip_number'] ?? '')),
                'pedigree_number' => trim((string) ($data['pedigree_number'] ?? '')),
                'sex' => (string) ($data['sex'] ?? 'unknown'),
                'birth_date' => (string) ($data['birth_date'] ?? ''),
                'health_summary' => trim((string) ($data['health_note'] ?? '')) ?: null,
                'status' => 'active',
            ]);
            $dogs->linkOwner($dogId, $ownerId, 'owner_qr');

            if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $stored = FileStorage::store($file, $breedSlug, $ownerId, $dogId, 'pedigree');
                $fileId = $files->create('dog', $dogId, $stored['original'], $stored['relative'], $stored['mime'], $stored['size'], null);
                $dogs->addHealthDocument($dogId, $ownerId, $fileId, 'rodokmen', null, null);
            }

            $this->insertConsent($pdo, (int) $sample['id'], $dogId, $ownerId, $data);
            (new SampleRepository())->attachDog((int) $sample['id'], $dogId);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        // Po commitu: pozvanka pro nastaveni hesla (mimo transakci - posila e-mail).
        try {
            (new InviteService())->sendPasswordInvite($ownerId, null);
        } catch (\Throwable $e) {
            // registrace probehla; pozvanku lze poslat znovu z adminu
        }

        return ['owner_id' => $ownerId, 'dog_id' => $dogId];
    }

    /** @param array<string, mixed> $data */
    private function insertConsent(\PDO $pdo, int $sampleId, int $dogId, int $ownerId, array $data): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO consents
                (sample_id, dog_id, owner_id, consent_version, research_consent, gdpr_consent,
                 future_contact_consent, results_consent, owner_name_at_consent, ip_address)
             VALUES
                (:s, :d, :o, :v, 1, 1, :fc, :rc, :name, :ip)'
        );
        $stmt->execute([
            's' => $sampleId,
            'd' => $dogId,
            'o' => $ownerId,
            'v' => self::CONSENT_VERSION,
            'fc' => !empty($data['future_contact_consent']) ? 1 : 0,
            'rc' => !empty($data['results_consent']) ? 1 : 0,
            'name' => trim((string) $data['owner_name']),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
