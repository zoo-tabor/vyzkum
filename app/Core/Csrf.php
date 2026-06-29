<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        $token = Session::get('_csrf');
        if (!is_string($token)) {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf', $token);
        }

        return $token;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    /** Pure check, safe to unit test (does not exit). */
    public static function isValid(mixed $posted): bool
    {
        return is_string($posted) && hash_equals(self::token(), $posted);
    }

    public static function verify(): void
    {
        if (!self::isValid($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            exit('Platnost formulare vyprsela. Vratte se prosim zpet a zkuste to znovu.');
        }
    }
}
