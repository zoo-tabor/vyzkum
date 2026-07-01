<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\DogRepository;
use App\Repositories\OwnerRepository;

/**
 * Onboarding majitele: kontrola kontaktu, potvrzeni psu (Ano/Ne -> prevod), souhlas.
 * Sdileno stránkou z pozvánky (heslo az na konci) i portalovym fallbackem.
 */
final class OwnerOnboardingService
{
    public function __construct(
        private OwnerRepository $owners = new OwnerRepository(),
        private DogRepository $dogs = new DogRepository(),
        private OwnershipTransferService $transfers = new OwnershipTransferService(),
    ) {
    }

    /**
     * Data pro vykresleni onboardingu.
     *
     * @return array{owner: array<string,mixed>|null, primaryEmail: ?string, secondaryEmails: array<int,array<string,mixed>>, phones: array<int,array<string,mixed>>, dogs: array<int,array<string,mixed>>}
     */
    public function viewData(int $ownerId): array
    {
        $owner = $this->owners->find($ownerId);
        $currentDogs = array_values(array_filter(
            $this->owners->dogsOf($ownerId),
            static fn (array $d): bool => (int) $d['is_current'] === 1
        ));

        return [
            'owner' => $owner,
            'primaryEmail' => $this->owners->primaryEmail($ownerId),
            'secondaryEmails' => array_values(array_filter($this->owners->emails($ownerId), static fn ($e) => (int) $e['is_primary'] === 0)),
            'phones' => $this->owners->phones($ownerId),
            'dogs' => $currentDogs,
        ];
    }

    /**
     * Ulozi odeslany onboarding (cte z POST): kontakty, potvrzeni/prevod psu, souhlas.
     *
     * @return array{confirmed:int, transfer:int}
     */
    public function applyFromRequest(int $ownerId, ?int $actingUserId): array
    {
        // Kontakty (primarni e-mail se nemeni - je to prihlasovaci jmeno).
        $this->owners->updateContactInfo($ownerId, trim((string) input('address')) ?: null);
        $this->owners->replacePhones($ownerId, $this->splitList((string) input('phones')));
        $this->owners->replaceSecondaryEmails($ownerId, $this->splitList((string) input('secondary_emails')));

        $confirmed = 0;
        $handedOver = 0;
        foreach ($this->owners->dogsOf($ownerId) as $d) {
            if ((int) $d['is_current'] !== 1) {
                continue;
            }
            $dogId = (int) $d['id'];
            if ((string) input('dog_' . $dogId) === 'transfer') {
                $name = trim((string) input('new_owner_name_' . $dogId));
                $email = trim((string) input('new_owner_email_' . $dogId));
                $phone = trim((string) input('new_owner_phone_' . $dogId));
                if ($name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->transfers->request($dogId, $ownerId, $name, $email, $phone ?: null, $actingUserId);
                    $handedOver++;
                }
            } else {
                $this->dogs->confirmOwnership($dogId, $ownerId);
                $confirmed++;
            }
        }

        $this->owners->markOnboarded($ownerId, true);
        AuditService::log($actingUserId, 'owner', 'owner_onboarding_completed', 'owner', (string) $ownerId, null, ['confirmed' => $confirmed, 'transfer' => $handedOver]);

        return ['confirmed' => $confirmed, 'transfer' => $handedOver];
    }

    /** @return array<int, string> */
    public function splitList(string $raw): array
    {
        $parts = array_map('trim', explode(';', $raw));
        return array_values(array_filter($parts, static fn (string $s): bool => $s !== ''));
    }
}
