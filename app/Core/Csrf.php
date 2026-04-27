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

    public static function verify(): void
    {
        $posted = $_POST['_csrf'] ?? '';
        if (!is_string($posted) || !hash_equals(self::token(), $posted)) {
            http_response_code(419);
            exit('Platnost formulare vyprsela. Vratte se prosim zpet a zkuste to znovu.');
        }
    }
}
