<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\UserRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\RateLimiter;
use App\Services\Totp;

final class TwoFactorController
{
    private const ISSUER = 'Vyzkum Zoo Tabor';
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 900;

    // --- Login challenge (user not yet authenticated) ---

    public function showChallenge(): string
    {
        if (!is_int(Session::get('2fa_pending_user_id'))) {
            redirect('/login');
        }
        return view('auth/two_factor', ['title' => 'Overeni 2FA']);
    }

    public function verifyChallenge(): string
    {
        Csrf::verify();

        $pendingId = Session::get('2fa_pending_user_id');
        if (!is_int($pendingId)) {
            redirect('/login');
        }

        $key = '2fa:' . ($_SERVER['REMOTE_ADDR'] ?? '') . ':' . $pendingId;
        if (RateLimiter::tooMany($key, self::MAX_ATTEMPTS, self::DECAY_SECONDS)) {
            return view('auth/two_factor', ['title' => 'Overeni 2FA', 'error' => 'Prilis mnoho pokusu. Zkuste to za chvili.']);
        }

        $user = (new UserRepository())->findById($pendingId);
        if ($user === null || empty($user['totp_secret'])) {
            Session::forget('2fa_pending_user_id');
            redirect('/login');
        }

        if (!Totp::verify((string) $user['totp_secret'], (string) input('code'))) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
            AuditService::log($pendingId, (string) $user['role'], '2fa_failed', 'user', (string) $pendingId);
            return view('auth/two_factor', ['title' => 'Overeni 2FA', 'error' => 'Neplatny overovaci kod.']);
        }

        RateLimiter::clear($key);
        Session::forget('2fa_pending_user_id');
        Auth::login($user);
        AuditService::log($pendingId, (string) $user['role'], 'login_2fa', 'user', (string) $pendingId);

        redirect('/admin');
    }

    // --- Enrollment / management (authenticated research_admin) ---

    public function setup(): string
    {
        $user = Auth::user();
        $enabled = !empty($user['totp_secret']);

        $secret = null;
        $uri = null;
        if (!$enabled) {
            $secret = Session::get('2fa_setup_secret');
            if (!is_string($secret)) {
                $secret = Totp::generateSecret();
                Session::put('2fa_setup_secret', $secret);
            }
            $uri = Totp::provisioningUri($secret, (string) $user['email'], self::ISSUER);
        }

        return view('admin/security', [
            'title' => 'Zabezpeceni',
            'enabled' => $enabled,
            'secret' => $secret,
            'uri' => $uri,
            'error' => Session::flash('security_error'),
            'notice' => Session::flash('security_notice'),
        ]);
    }

    public function enable(): string
    {
        Csrf::verify();

        $secret = Session::get('2fa_setup_secret');
        if (!is_string($secret)) {
            Session::flash('security_error', 'Relace pro nastaveni 2FA vyprsela, zkuste to znovu.');
            redirect('/admin/security');
        }

        if (!Totp::verify($secret, (string) input('code'))) {
            Session::flash('security_error', 'Kod nesedi. Zkontrolujte cas v aplikaci a zkuste znovu.');
            redirect('/admin/security');
        }

        (new UserRepository())->setTotpSecret((int) Auth::id(), $secret);
        Session::forget('2fa_setup_secret');
        AuditService::log(Auth::id(), Auth::role(), '2fa_enabled', 'user', (string) Auth::id());
        Auth::flush();

        Session::flash('security_notice', 'Dvoufaktorove overeni bylo aktivovano.');
        redirect('/admin/security');
    }

    public function disable(): string
    {
        Csrf::verify();

        $user = Auth::user();
        if (empty($user['totp_secret']) || !Totp::verify((string) $user['totp_secret'], (string) input('code'))) {
            Session::flash('security_error', 'Pro vypnuti 2FA zadejte platny aktualni kod.');
            redirect('/admin/security');
        }

        (new UserRepository())->setTotpSecret((int) Auth::id(), null);
        AuditService::log(Auth::id(), Auth::role(), '2fa_disabled', 'user', (string) Auth::id());
        Auth::flush();

        Session::flash('security_notice', 'Dvoufaktorove overeni bylo vypnuto.');
        redirect('/admin/security');
    }

    public function changePassword(): string
    {
        Csrf::verify();

        $user = Auth::user();
        $current = (string) input('current_password');
        $new = (string) input('new_password');
        $confirm = (string) input('new_password_confirm');

        if (empty($user['password_hash']) || !password_verify($current, (string) $user['password_hash'])) {
            Session::flash('security_error', 'Soucasne heslo nesedi.');
            redirect('/admin/security');
        }
        if (strlen($new) < 10) {
            Session::flash('security_error', 'Nove heslo musi mit aspon 10 znaku.');
            redirect('/admin/security');
        }
        if ($new !== $confirm) {
            Session::flash('security_error', 'Nova hesla se neshoduji.');
            redirect('/admin/security');
        }

        (new UserRepository())->updatePasswordHash((int) Auth::id(), Auth::hash($new));
        AuditService::log(Auth::id(), Auth::role(), 'password_changed', 'user', (string) Auth::id());
        Auth::flush();

        Session::flash('security_notice', 'Heslo bylo zmeneno.');
        redirect('/admin/security');
    }
}
