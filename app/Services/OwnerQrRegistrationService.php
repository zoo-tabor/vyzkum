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
 * Registrace psa majitelem pres QR: zalozi/najde majitele, psa, vazbu, rodokmen
 * (souborovy system) a napoji na vzorek. Informovany souhlas (/gdpr) je povinny a
 * uklada se do owners.contact_consent (jako u onboardingu). Po commitu posle
 * pozvanku pro nastaveni hesla (pristup do portalu).
 */
final class OwnerQrRegistrationService
{
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
                    // Informovany souhlas (/gdpr) je povinny (validace ve formulari),
                    // takze pri registraci je vzdy udelen - jako markOnboarded(true).
                    'contact_consent' => true,
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
}
