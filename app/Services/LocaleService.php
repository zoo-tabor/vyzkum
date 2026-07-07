<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\OwnerRepository;
use App\Support\I18n;

/**
 * Urceni a perzistence jazyka rozhrani.
 * Priorita: session -> cookie (pamet zarizeni) -> owners.language (ucet majitele)
 * -> Accept-Language -> vychozi (cs). Prepnuti uklada do session + cookie a u
 * prihlaseneho majitele i do owners.language.
 */
final class LocaleService
{
    private const COOKIE = 'locale';
    private const COOKIE_TTL = 31536000; // 1 rok

    /** Zavola se v bootstrapu: urci jazyk a nastavi ho pro cely request. */
    public static function boot(): void
    {
        $locale = self::detect();
        // Ulozime do session, aby dalsi requesty nemusely znovu resolvovat (owner lookup).
        Session::put('locale', $locale);
        I18n::setLocale($locale);
    }

    /** Po prihlaseni: preference uctu majitele prebije anonymni volbu. */
    public static function applyForUser(int $userId): void
    {
        $language = self::ownerLanguage($userId);
        if ($language === null) {
            return;
        }
        Session::put('locale', $language);
        self::writeCookie($language);
        I18n::setLocale($language);
    }

    /** Prepnuti jazyka uzivatelem (routa /locale/{lang}). */
    public static function switchTo(string $locale): void
    {
        if (!I18n::isValid($locale)) {
            return;
        }
        Session::put('locale', $locale);
        self::writeCookie($locale);

        $userId = Auth::id();
        if ($userId !== null) {
            $repo = new OwnerRepository();
            $owner = $repo->findByUserId($userId);
            if ($owner !== null) {
                $repo->setLanguage((int) $owner['id'], $locale);
            }
        }
        I18n::setLocale($locale);
    }

    private static function detect(): string
    {
        $session = Session::get('locale');
        if (is_string($session) && I18n::isValid($session)) {
            return $session;
        }
        $cookie = $_COOKIE[self::COOKIE] ?? null;
        if (is_string($cookie) && I18n::isValid($cookie)) {
            return $cookie;
        }
        $userId = Auth::id();
        if ($userId !== null) {
            $language = self::ownerLanguage($userId);
            if ($language !== null) {
                return $language;
            }
        }
        $fromHeader = self::fromAcceptLanguage();
        if ($fromHeader !== null) {
            return $fromHeader;
        }
        return I18n::defaultLocale();
    }

    private static function ownerLanguage(int $userId): ?string
    {
        $owner = (new OwnerRepository())->findByUserId($userId);
        $language = $owner['language'] ?? null;
        return (is_string($language) && $language !== '' && I18n::isValid($language)) ? $language : null;
    }

    private static function fromAcceptLanguage(): ?string
    {
        $header = (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        if ($header === '') {
            return null;
        }
        foreach (explode(',', $header) as $part) {
            $code = strtolower(trim(explode(';', $part)[0]));
            $code = substr($code, 0, 2); // en-US -> en
            if ($code !== '' && I18n::isValid($code)) {
                return $code;
            }
        }
        return null;
    }

    private static function writeCookie(string $locale): void
    {
        $_COOKIE[self::COOKIE] = $locale;
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }
        setcookie(self::COOKIE, $locale, [
            'expires' => time() + self::COOKIE_TTL,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
