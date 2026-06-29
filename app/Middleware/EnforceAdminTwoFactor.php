<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Session;
use App\Services\Auth;

/**
 * research_admin accounts must have 2FA enabled. Until they do, every admin
 * request is redirected to the security page (which is itself whitelisted so the
 * user can actually enroll, and logout stays reachable).
 */
final class EnforceAdminTwoFactor implements Middleware
{
    private const ALLOWED = [
        '/admin/security',
        '/admin/security/enable',
        '/admin/security/disable',
        '/admin/security/password',
        '/logout',
    ];

    public function handle(Request $request): void
    {
        // Lze vypnout pro vyvoj pres .env (ENFORCE_ADMIN_2FA=false).
        if (defined('ENFORCE_ADMIN_2FA') && ENFORCE_ADMIN_2FA === false) {
            return;
        }

        $user = Auth::user();
        if ($user === null || ($user['role'] ?? '') !== 'research_admin') {
            return;
        }
        if (!empty($user['totp_secret'])) {
            return;
        }
        if (in_array($request->path(), self::ALLOWED, true)) {
            return;
        }

        Session::flash('security_notice', 'Pro ucet vyzkumneho admina je nutne nejprve aktivovat dvoufaktorove overeni.');
        redirect('/admin/security');
    }
}
