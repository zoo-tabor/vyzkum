<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Repositories\InviteRepository;
use App\Repositories\OwnerRepository;
use App\Repositories\UserRepository;

final class InviteService
{
    public function __construct(
        private OwnerRepository $owners = new OwnerRepository(),
        private UserRepository $users = new UserRepository(),
        private InviteRepository $invites = new InviteRepository(),
    ) {
    }

    /**
     * Vytvori uctu majitele (pokud neni), pozvanku s platnosti 1 mesic a posle
     * e-mail s odkazem pro nastaveni hesla.
     *
     * @return array{ok: bool, message: string}
     */
    public function sendPasswordInvite(int $ownerId, ?int $createdByUserId): array
    {
        $owner = $this->owners->find($ownerId);
        if ($owner === null) {
            return ['ok' => false, 'message' => 'Majitel nenalezen.'];
        }

        $email = $this->owners->primaryEmail($ownerId);
        if ($email === null || $email === '') {
            return ['ok' => false, 'message' => 'Majitel nema primarni e-mail - nejdrive ho doplnte.'];
        }

        $userId = $this->users->ensureUser($email, 'owner');
        if (empty($owner['user_id'])) {
            $this->owners->linkUser($ownerId, $userId);
        }

        $token = TokenService::generate();
        $expiresAt = (new \DateTimeImmutable('+1 month'))->format('Y-m-d H:i:s');
        $this->invites->create($userId, $ownerId, TokenService::hash($token), 'set_password', $expiresAt, $createdByUserId);

        $appUrl = rtrim((string) Config::instance()->get('APP_URL', ''), '/');
        $link = $appUrl . '/set-password/' . $token;

        $subject = 'Nastaveni hesla - Vyzkum Zoo Tabor';
        $body = $this->buildBody((string) $owner['display_name'], $link);
        $sent = MailService::send($email, $subject, $body, 'set_password');

        if (!$sent) {
            return ['ok' => false, 'message' => 'Pozvanka vytvorena, ale e-mail se nepodarilo odeslat (viz email log).'];
        }

        return ['ok' => true, 'message' => 'Odkaz pro nastaveni hesla byl odeslan na ' . $email . '.'];
    }

    private function buildBody(string $name, string $link): string
    {
        return "Dobry den,\n\n"
            . "do systemu vyzkumu plemen psu Zoo Tabor vam byl zalozen ucet.\n"
            . "Pro nastaveni hesla pouzijte tento odkaz (plati 1 mesic):\n\n"
            . $link . "\n\n"
            . "Po nastaveni hesla se budete moci prihlasit a videt sve psy.\n\n"
            . "S pozdravem\nVyzkumny tym Zoo Tabor";
    }
}
