<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Repositories\DogRepository;
use App\Repositories\OwnerRepository;
use App\Repositories\TransferRepository;
use App\Repositories\UserRepository;

final class OwnershipTransferService
{
    public function __construct(
        private TransferRepository $transfers = new TransferRepository(),
        private OwnerRepository $owners = new OwnerRepository(),
        private DogRepository $dogs = new DogRepository(),
        private UserRepository $users = new UserRepository(),
        private InviteService $invites = new InviteService(),
    ) {
    }

    /** Puvodni majitel zada noveho; posle se mu potvrzovaci odkaz. */
    public function request(int $dogId, ?int $fromOwnerId, string $newName, string $newEmail, ?int $createdBy): array
    {
        $email = strtolower(trim($newEmail));
        $token = TokenService::generate();
        $expiresAt = (new \DateTimeImmutable('+1 month'))->format('Y-m-d H:i:s');
        $this->transfers->create($dogId, $fromOwnerId, trim($newName), $email, TokenService::hash($token), $expiresAt, $createdBy);

        $appUrl = rtrim((string) Config::instance()->get('APP_URL', ''), '/');
        $link = $appUrl . '/transfer/' . $token;
        $body = "Dobry den,\n\n"
            . "stavajici majitel vas uvedl jako noveho majitele psa v ramci vyzkumu plemen psu Zoo Tabor.\n"
            . "Pro potvrzeni prevzeti psa pouzijte tento odkaz (plati 1 mesic):\n\n"
            . $link . "\n\n"
            . "Po potvrzeni vam prijde odkaz pro nastaveni hesla do portalu.\n\n"
            . "S pozdravem\nVyzkumny tym Zoo Tabor";

        $sent = MailService::send($email, 'Prevzeti psa - Vyzkum Zoo Tabor', $body, 'ownership_transfer');
        return ['ok' => $sent, 'message' => $sent
            ? 'Novemu majiteli (' . $email . ') byl odeslan potvrzovaci odkaz.'
            : 'Zadost vytvorena, ale e-mail se nepodarilo odeslat (viz email log).'];
    }

    /**
     * Novy majitel potvrdil: vlastnictvi se automaticky prevede, posle se pozvanka k heslu.
     *
     * @param array<string, mixed> $request
     * @return array{owner_id: int}
     */
    public function confirm(array $request): array
    {
        $email = strtolower(trim((string) $request['new_owner_email']));

        $ownerId = $this->owners->findByPrimaryEmail($email);
        if ($ownerId === null) {
            $ownerId = $this->owners->create([
                'display_name' => (string) $request['new_owner_name'],
                'preferred_contact_method' => 'email',
            ]);
            $this->owners->addEmail($ownerId, $email, true);
        }

        // Atomicka vymena aktualniho vlastnictvi (uzavre stare, zalozi nove).
        $this->dogs->setCurrentOwner((int) $request['dog_id'], $ownerId, 'transfer');

        $userId = $this->users->ensureUser($email, 'owner');
        $owner = $this->owners->find($ownerId);
        if ($owner !== null && empty($owner['user_id'])) {
            $this->owners->linkUser($ownerId, $userId);
        }

        $this->transfers->markConfirmed((int) $request['id']);
        AuditService::log(null, 'owner', 'ownership_transferred', 'dog', (string) $request['dog_id'], null, ['to_owner_id' => $ownerId]);

        try {
            $this->invites->sendPasswordInvite($ownerId, null);
        } catch (\Throwable $e) {
            // prevod probehl; pozvanku lze poslat znovu z adminu
        }

        return ['owner_id' => $ownerId];
    }
}
