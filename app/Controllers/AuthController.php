<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\RateLimiter;

final class AuthController
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 900; // 15 minut

    public function showLogin(): string
    {
        if (Auth::check()) {
            redirect(home_for(Auth::role()));
        }
        return view('auth/login', ['title' => 'Přihlášení']);
    }

    public function login(): string
    {
        Csrf::verify();

        $email = strtolower(trim((string) input('email')));
        $password = (string) input('password');
        $key = 'login:' . ($_SERVER['REMOTE_ADDR'] ?? '') . ':' . $email;

        if (RateLimiter::tooMany($key, self::MAX_ATTEMPTS, self::DECAY_SECONDS)) {
            return view('auth/login', [
                'title' => 'Přihlášení',
                'error' => 'Příliš mnoho pokusů o přihlášení. Zkuste to prosím za chvíli.',
                'email' => $email,
            ]);
        }

        $user = Auth::attempt($email, $password);
        if ($user === null) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
            AuditService::log(null, null, 'login_failed', 'user', $email !== '' ? $email : null);
            return view('auth/login', [
                'title' => 'Přihlášení',
                'error' => 'Neplatné přihlašovací údaje.',
                'email' => $email,
            ]);
        }

        RateLimiter::clear($key);

        // If 2FA is enabled, defer the actual login until the TOTP code is verified.
        if (!empty($user['totp_secret'])) {
            Session::put('2fa_pending_user_id', (int) $user['id']);
            redirect('/2fa');
        }

        Auth::login($user);
        // Preference jazyka uctu (majitel) prebije anonymni volbu z login page.
        \App\Services\LocaleService::applyForUser((int) $user['id']);
        AuditService::log((int) $user['id'], (string) $user['role'], 'login', 'user', (string) $user['id']);

        redirect(home_for((string) $user['role']));
    }

    public function logout(): string
    {
        Csrf::verify();

        $id = Auth::id();
        $role = Auth::role();
        Auth::logout();

        if ($id !== null) {
            AuditService::log($id, $role, 'logout', 'user', (string) $id);
        }

        redirect('/login');
    }
}
