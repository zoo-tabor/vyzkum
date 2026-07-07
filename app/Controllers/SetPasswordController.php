<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\InviteRepository;
use App\Repositories\UserRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\OwnerOnboardingService;
use App\Services\TokenService;

final class SetPasswordController
{
    public function show(string $token): string
    {
        $invite = (new InviteRepository())->findActiveByHash(TokenService::hash($token));
        if ($invite === null) {
            return view('auth/set_password', ['title' => 'Nastavení hesla', 'invalid' => true, 'token' => $token]);
        }

        $data = $this->onboardingData($invite);
        if ($data !== null) {
            return view('auth/onboarding', array_merge($data, [
                'title' => 'Dokončení registrace',
                'token' => $token,
                'error' => null,
                'old' => [],
                '_layout' => 'public',
            ]));
        }

        return view('auth/set_password', ['title' => 'Nastavení hesla', 'invalid' => false, 'token' => $token]);
    }

    public function submit(string $token): string
    {
        Csrf::verify();

        $invites = new InviteRepository();
        $invite = $invites->findActiveByHash(TokenService::hash($token));
        if ($invite === null) {
            return view('auth/set_password', ['title' => 'Nastavení hesla', 'invalid' => true, 'token' => $token]);
        }

        $password = (string) input('password');
        $confirm = (string) input('password_confirm');
        $pwError = null;
        if (strlen($password) < 10) {
            $pwError = t('Heslo musí mít alespoň 10 znaků.');
        } elseif ($password !== $confirm) {
            $pwError = t('Hesla se neshodují.');
        }

        // Majitel bez dokonceneho onboardingu: nejdriv projde udaje/psy/souhlas, heslo az na konci.
        $data = $this->onboardingData($invite);
        if ($data !== null) {
            $consent = !empty(input('main_consent'));
            if ($pwError !== null || !$consent) {
                return view('auth/onboarding', array_merge($data, [
                    'title' => 'Dokončení registrace',
                    'token' => $token,
                    'error' => $pwError ?? t('Bez souhlasu se zpracováním údajů nelze registraci dokončit.'),
                    'old' => $_POST,
                    '_layout' => 'public',
                ]));
            }
            (new OwnerOnboardingService())->applyFromRequest((int) $invite['owner_id'], null);
            return $this->finish($invites, $invite, $password, '/portal');
        }

        // Ostatni (klubove ucty apod.) nebo jiz onboardovani majitele: jen heslo.
        if ($pwError !== null) {
            return view('auth/set_password', ['title' => 'Nastavení hesla', 'invalid' => false, 'token' => $token, 'error' => $pwError]);
        }
        return $this->finish($invites, $invite, $password, null);
    }

    /**
     * Vrati data pro onboarding, pokud pozvanka patri majiteli, ktery jeste
     * onboardingem neprosel. Jinak null (klubovy ucet / reset hesla).
     *
     * @param array<string, mixed> $invite
     * @return array<string, mixed>|null
     */
    private function onboardingData(array $invite): ?array
    {
        if (empty($invite['owner_id'])) {
            return null;
        }
        $data = (new OwnerOnboardingService())->viewData((int) $invite['owner_id']);
        if ($data['owner'] === null || !empty($data['owner']['onboarding_completed_at'])) {
            return null;
        }
        return $data;
    }

    /**
     * Nastavi heslo, oznaci pozvanku, prihlasi a presmeruje.
     *
     * @param array<string, mixed> $invite
     */
    private function finish(InviteRepository $invites, array $invite, string $password, ?string $redirectTo): string
    {
        $users = new UserRepository();
        $userId = (int) $invite['user_id'];
        $users->updatePasswordHash($userId, Auth::hash($password));
        $invites->markUsed((int) $invite['id']);

        $user = $users->findById($userId);
        if ($user === null) {
            redirect('/login');
        }

        Auth::login($user);
        AuditService::log($userId, (string) $user['role'], 'password_set_via_invite', 'user', (string) $userId);

        redirect($redirectTo ?? home_for((string) $user['role']));
    }
}
