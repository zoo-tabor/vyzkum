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
    public function request(int $dogId, ?int $fromOwnerId, string $newName, string $newEmail, ?string $newPhone, ?int $createdBy): array
    {
        $email = strtolower(trim($newEmail));
        $phone = $newPhone !== null && trim($newPhone) !== '' ? trim($newPhone) : null;
        $token = TokenService::generate();
        $expiresAt = (new \DateTimeImmutable('+1 month'))->format('Y-m-d H:i:s');
        $this->transfers->create($dogId, $fromOwnerId, trim($newName), $email, $phone, TokenService::hash($token), $expiresAt, $createdBy);

        $appUrl = rtrim((string) Config::instance()->get('APP_URL', ''), '/');
        $link = $appUrl . '/transfer/' . $token;
        $body = "Dobrý den,\n\n"
            . "stávající majitel vás uvedl jako nového majitele psa v rámci výzkumu plemen psů ZOO Tábor.\n"
            . "Pro potvrzení převzetí psa použijte tento odkaz (platí 1 měsíc):\n\n"
            . $link . "\n\n"
            . "Po potvrzení vám přijde odkaz pro nastavení hesla do portálu.\n\n"
            . "S pozdravem\nVýzkumný tým ZOO Tábor";

        $sent = MailService::send($email, 'Převzetí psa - Výzkum ZOO Tábor', $body, 'ownership_transfer');
        return ['ok' => $sent, 'message' => $sent
            ? 'Novému majiteli (' . $email . ') byl odeslán potvrzovací odkaz.'
            : 'Žádost vytvořena, ale e-mail se nepodařilo odeslat (viz email log).'];
    }

    /**
     * Novy majitel potvrdil: vlastnictvi se automaticky prevede. Pozvanka k heslu
     * se posle jen tehdy, kdyz novy majitel jeste nema ucet s heslem (jinak se uz
     * prihlasi svym stavajicim heslem).
     *
     * @param array<string, mixed> $request
     * @return array{owner_id: int, invite_sent: bool}
     */
    public function confirm(array $request): array
    {
        $email = strtolower(trim((string) $request['new_owner_email']));

        // Ucet uz existuje a ma heslo? Pak pozvanku neposilame.
        $existingUser = $this->users->findByEmail($email);
        $alreadyHasPassword = $existingUser !== null && !empty($existingUser['password_hash']);

        $ownerId = $this->owners->findByPrimaryEmail($email);
        if ($ownerId === null) {
            $ownerId = $this->owners->create([
                'display_name' => (string) $request['new_owner_name'],
                'preferred_contact_method' => 'email',
            ]);
            $this->owners->addEmail($ownerId, $email, true);
            $phone = trim((string) ($request['new_owner_phone'] ?? ''));
            if ($phone !== '') {
                $this->owners->addPhone($ownerId, $phone, null, true);
            }
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

        $inviteSent = false;
        if (!$alreadyHasPassword) {
            try {
                $this->invites->sendPasswordInvite($ownerId, null);
                $inviteSent = true;
            } catch (\Throwable $e) {
                // prevod probehl; pozvanku lze poslat znovu z adminu
            }
        }

        return ['owner_id' => $ownerId, 'invite_sent' => $inviteSent];
    }
}
