<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

final class AdminAuth
{
    public function __construct(private Config $config)
    {
    }

    public function requireAdmin(): void
    {
        $username = (string) $this->config->get('ADMIN_USERNAME', 'admin');
        $hash = (string) $this->config->get('ADMIN_PASSWORD_HASH', '');

        [$givenUser, $givenPass] = $this->credentials();

        $ok = $givenUser === $username && $hash !== '' && password_verify($givenPass, $hash);
        if ($ok) {
            return;
        }

        header('WWW-Authenticate: Basic realm="Dog research admin"');
        http_response_code(401);
        exit('Administrace vyžaduje přihlášení.');
    }

    /** @return array{0:string, 1:string} */
    private function credentials(): array
    {
        $user = (string) ($_SERVER['PHP_AUTH_USER'] ?? '');
        $pass = (string) ($_SERVER['PHP_AUTH_PW'] ?? '');

        if ($user !== '') {
            return [$user, $pass];
        }

        $header = (string) (
            $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? ''
        );

        if (stripos($header, 'Basic ') !== 0) {
            return ['', ''];
        }

        $decoded = base64_decode(substr($header, 6), true);
        if (!is_string($decoded) || !str_contains($decoded, ':')) {
            return ['', ''];
        }

        [$user, $pass] = explode(':', $decoded, 2);
        return [$user, $pass];
    }
}
