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
            return ['ok' => false, 'message' => 'Majitel nemá primární e-mail - nejdříve ho doplňte.'];
        }

        $userId = $this->users->ensureUser($email, 'owner');
        if (empty($owner['user_id'])) {
            $this->owners->linkUser($ownerId, $userId);
        }

        return $this->createAndSend($userId, $ownerId, $email, (string) $owner['display_name'], $createdByUserId);
    }

    /**
     * Pozvanka pro existujiciho uzivatele (napr. klubovy ucet) - bez vazby na majitele.
     *
     * @return array{ok: bool, message: string}
     */
    public function sendUserInvite(int $userId, string $email, ?int $createdByUserId): array
    {
        return $this->createAndSend($userId, null, $email, $email, $createdByUserId);
    }

    /**
     * Obnova hesla ze strany uzivatele (odkaz "Zapomneli jste heslo?"). Najde ucet
     * podle e-mailu; kdyz existuje a je aktivni, posle odkaz na obnovu s kratsi
     * platnosti. Volajici NESMI prozradit, jestli e-mail existuje - proto void.
     */
    public function sendPasswordReset(string $email): void
    {
        $email = strtolower(trim($email));
        $user = $this->users->findByEmail($email);
        if ($user === null || ($user['status'] ?? 'active') !== 'active') {
            return;
        }

        $owner = $this->owners->findByUserId((int) $user['id']);
        $ownerId = $owner !== null ? (int) $owner['id'] : null;
        $name = $owner !== null ? (string) $owner['display_name'] : $email;

        $this->createAndSend((int) $user['id'], $ownerId, $email, $name, null, true);
    }

    /** @return array{ok: bool, message: string} */
    private function createAndSend(int $userId, ?int $ownerId, string $email, string $name, ?int $createdByUserId, bool $isReset = false): array
    {
        $token = TokenService::generate();
        // Reset hesla ma kratkou platnost, pozvanka noveho uctu 1 mesic.
        $expiresAt = (new \DateTimeImmutable($isReset ? '+2 hours' : '+1 month'))->format('Y-m-d H:i:s');
        $this->invites->create($userId, $ownerId, TokenService::hash($token), 'set_password', $expiresAt, $createdByUserId);

        $appUrl = rtrim((string) Config::instance()->get('APP_URL', ''), '/');
        $link = $appUrl . '/set-password/' . $token;

        // Jazyk prijemce = preferovany jazyk majitele (kdyz je znamy), jinak cestina.
        $locale = null;
        if ($ownerId !== null) {
            $lang = (string) ($this->owners->find($ownerId)['language'] ?? '');
            $locale = $lang !== '' ? $lang : null;
        }

        $key = $isReset ? 'password_reset' : 'set_password';
        $sent = MailTemplateService::send($key, $email, ['odkaz' => $link, 'jmeno' => $name], $locale);

        if (!$sent) {
            return ['ok' => false, 'message' => 'Pozvánka vytvořena, ale e-mail se nepodařilo odeslat (viz email log).'];
        }

        return ['ok' => true, 'message' => 'Odkaz pro nastavení hesla byl odeslán na ' . $email . '.'];
    }
}
