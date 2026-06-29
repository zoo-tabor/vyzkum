<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Repositories\InviteRepository;
use App\Repositories\UserRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\TokenService;

final class SetPasswordController
{
    public function show(string $token): string
    {
        $invite = (new InviteRepository())->findActiveByHash(TokenService::hash($token));
        if ($invite === null) {
            return view('auth/set_password', [
                'title' => 'Nastaveni hesla',
                'invalid' => true,
                'token' => $token,
            ]);
        }

        return view('auth/set_password', [
            'title' => 'Nastaveni hesla',
            'invalid' => false,
            'token' => $token,
        ]);
    }

    public function submit(string $token): string
    {
        Csrf::verify();

        $invites = new InviteRepository();
        $invite = $invites->findActiveByHash(TokenService::hash($token));
        if ($invite === null) {
            return view('auth/set_password', ['title' => 'Nastaveni hesla', 'invalid' => true, 'token' => $token]);
        }

        $password = (string) input('password');
        $confirm = (string) input('password_confirm');
        if (strlen($password) < 10) {
            return view('auth/set_password', ['title' => 'Nastaveni hesla', 'invalid' => false, 'token' => $token,
                'error' => 'Heslo musi mit aspon 10 znaku.']);
        }
        if ($password !== $confirm) {
            return view('auth/set_password', ['title' => 'Nastaveni hesla', 'invalid' => false, 'token' => $token,
                'error' => 'Hesla se neshoduji.']);
        }

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

        redirect(home_for((string) $user['role']));
    }
}
