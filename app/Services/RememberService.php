<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\RememberTokenRepository;
use App\Repositories\UserRepository;

/**
 * Trvale prihlaseni ("zapamatovat"). Nahodny token je v httponly cookie na zarizeni,
 * v DB jen jeho hash. Pri kazdem pouziti se token ROTUJE (smaze stary, vyda novy).
 * Vydava se az po plne autentizaci (vc. 2FA), takze auto-login pres cookie 2FA preskoci.
 */
final class RememberService
{
    private const COOKIE = 'remember';
    private const DAYS = 90;

    public static function issue(int $userId): void
    {
        $token = TokenService::generate();
        $expires = new \DateTimeImmutable('+' . self::DAYS . ' days');
        (new RememberTokenRepository())->create($userId, TokenService::hash($token), $expires->format('Y-m-d H:i:s'));
        self::setCookie($token, $expires->getTimestamp());
    }

    /**
     * Zkusi auto-login z remember cookie. Pri uspechu token rotuje a vraci uzivatele.
     *
     * @return array<string, mixed>|null
     */
    public static function attempt(): ?array
    {
        $token = (string) ($_COOKIE[self::COOKIE] ?? '');
        if ($token === '' || !ctype_xdigit($token)) {
            return null;
        }

        $repo = new RememberTokenRepository();
        $row = $repo->findValid(TokenService::hash($token));
        if ($row === null) {
            self::clearCookie();
            return null;
        }

        $user = (new UserRepository())->findById((int) $row['user_id']);
        if ($user === null || ($user['status'] ?? '') !== 'active') {
            $repo->delete((int) $row['id']);
            self::clearCookie();
            return null;
        }

        // Rotace: pouzity token zneplatnit a vydat novy.
        $repo->delete((int) $row['id']);
        self::issue((int) $user['id']);
        return $user;
    }

    /** Odhlaseni tohoto zarizeni: zrusi token v DB i cookie. */
    public static function clear(): void
    {
        $token = (string) ($_COOKIE[self::COOKIE] ?? '');
        if ($token !== '') {
            (new RememberTokenRepository())->deleteByHash(TokenService::hash($token));
        }
        self::clearCookie();
    }

    private static function setCookie(string $token, int $expiresTs): void
    {
        setcookie(self::COOKIE, $token, [
            'expires' => $expiresTs, 'path' => '/', 'secure' => self::isHttps(),
            'httponly' => true, 'samesite' => 'Lax',
        ]);
        $_COOKIE[self::COOKIE] = $token;
    }

    private static function clearCookie(): void
    {
        setcookie(self::COOKIE, '', [
            'expires' => time() - 3600, 'path' => '/', 'secure' => self::isHttps(),
            'httponly' => true, 'samesite' => 'Lax',
        ]);
        unset($_COOKIE[self::COOKIE]);
    }

    private static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
            return true;
        }
        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }
}
