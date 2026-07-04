<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\InviteService;
use App\Services\RateLimiter;

final class ForgotPasswordController
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 900; // 15 minut

    public function show(): string
    {
        if (Auth::check()) {
            redirect(home_for(Auth::role()));
        }
        return view('auth/forgot_password', ['title' => 'Obnova hesla', 'sent' => false]);
    }

    public function submit(): string
    {
        Csrf::verify();

        $email = strtolower(trim((string) input('email')));
        $key = 'forgot:' . ($_SERVER['REMOTE_ADDR'] ?? '') . ':' . $email;

        // Odkaz posilame jen u platneho e-mailu a v ramci rate-limitu; hlaska je
        // ale vzdy stejna, aby se neprozradilo, jestli ucet existuje.
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && !RateLimiter::tooMany($key, self::MAX_ATTEMPTS, self::DECAY_SECONDS)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
            (new InviteService())->sendPasswordReset($email);
            AuditService::log(null, null, 'password_reset_requested', 'user', $email);
        }

        return view('auth/forgot_password', ['title' => 'Obnova hesla', 'sent' => true, 'email' => $email]);
    }
}
