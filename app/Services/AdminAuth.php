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

        $givenUser = $_SERVER['PHP_AUTH_USER'] ?? '';
        $givenPass = $_SERVER['PHP_AUTH_PW'] ?? '';

        $ok = $givenUser === $username && $hash !== '' && password_verify($givenPass, $hash);
        if ($ok) {
            return;
        }

        header('WWW-Authenticate: Basic realm="Dog research admin"');
        http_response_code(401);
        exit('Administrace vyzaduje prihlaseni.');
    }
}
