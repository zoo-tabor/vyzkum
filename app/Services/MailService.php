<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;

/**
 * Odesilani e-mailu pres SMTP (STARTTLS + AUTH LOGIN), bez externich knihoven.
 * Kdyz MAIL_ENABLED=false, e-mail se jen zaloguje (vcetne tela s odkazem) do
 * storage/logs/mail.log - vhodne pro vyvoj. Kazde odeslani se zapise do email_log.
 */
final class MailService
{
    public static function send(string $to, string $subject, string $body, string $template = 'generic'): bool
    {
        $cfg = Config::instance();
        $enabled = (bool) $cfg->get('MAIL_ENABLED', false);
        // Vychozi transport je PHP mail() - na wedos je odchozi SMTP (port 25) blokovan.
        $transport = strtolower((string) $cfg->get('MAIL_TRANSPORT', 'mail'));

        if (!$enabled) {
            self::logFile("[DISABLED] To: {$to}\nSubject: {$subject}\n\n{$body}\n" . str_repeat('-', 60) . "\n");
            self::record($to, $subject, $template, 'logged', null);
            return true;
        }

        try {
            if ($transport === 'smtp') {
                self::smtpSend($cfg, $to, $subject, $body);
            } else {
                self::mailSend($cfg, $to, $subject, $body);
            }
            self::record($to, $subject, $template, 'sent', null);
            return true;
        } catch (\Throwable $e) {
            self::logFile("[FAILED] To: {$to} | {$subject} | " . $e->getMessage() . "\n");
            self::record($to, $subject, $template, 'failed', substr($e->getMessage(), 0, 255));
            return false;
        }
    }

    /**
     * Diagnostika SMTP bez odeslani mailu: connect -> banner -> EHLO -> STARTTLS
     * -> AUTH. Hesla se nevypisuji. Pro overeni dosazitelnosti z daneho serveru.
     *
     * @return array{ok: bool, config: array<string, mixed>, steps: array<int, array{step:string, ok:bool, detail:string}>, error: ?string}
     */
    public static function diagnose(): array
    {
        $cfg = Config::instance();
        $host = (string) $cfg->get('SMTP_HOST', '');
        $port = (int) ($cfg->get('SMTP_PORT', 25) ?: 25);
        $user = (string) $cfg->get('SMTP_USER', '');
        $pass = (string) $cfg->get('SMTP_PASS', '');
        $startTls = (bool) $cfg->get('SMTP_USE_STARTTLS', true);
        $ehlo = parse_url((string) $cfg->get('APP_URL', 'localhost'), PHP_URL_HOST) ?: 'localhost';

        $steps = [];
        $error = null;
        $add = static function (string $step, bool $ok, string $detail = '') use (&$steps): void {
            $steps[] = ['step' => $step, 'ok' => $ok, 'detail' => $detail];
        };

        try {
            if ($host === '') {
                throw new \RuntimeException('SMTP_HOST neni nastaven.');
            }
            $t0 = microtime(true);
            $fp = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 12);
            if ($fp === false) {
                $add('connect ' . $host . ':' . $port, false, "{$errstr} ({$errno})");
                throw new \RuntimeException('Spojeni se nepodarilo navazat (port nejspis blokovan).');
            }
            $add('connect ' . $host . ':' . $port, true, sprintf('%.0f ms', (microtime(true) - $t0) * 1000));
            stream_set_timeout($fp, 12);

            $banner = trim((string) fgets($fp, 515));
            $add('banner', str_starts_with($banner, '220'), $banner !== '' ? $banner : '(zadna odpoved - timeout)');

            fwrite($fp, "EHLO {$ehlo}\r\n");
            $caps = self::readAll($fp);
            $add('EHLO', str_contains($caps, '250'), $caps);

            if ($startTls) {
                fwrite($fp, "STARTTLS\r\n");
                $r = trim((string) fgets($fp, 515));
                $okTls = str_starts_with($r, '220');
                $add('STARTTLS', $okTls, $r);
                if ($okTls) {
                    $enc = stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                    $add('TLS handshake', $enc === true, $enc === true ? 'OK' : 'selhalo');
                    if ($enc === true) {
                        fwrite($fp, "EHLO {$ehlo}\r\n");
                        self::readAll($fp);
                    }
                }
            }

            if ($user !== '') {
                fwrite($fp, "AUTH LOGIN\r\n");
                fgets($fp, 515);
                fwrite($fp, base64_encode($user) . "\r\n");
                fgets($fp, 515);
                fwrite($fp, base64_encode($pass) . "\r\n");
                $r = trim((string) fgets($fp, 515));
                $add('AUTH LOGIN', str_starts_with($r, '235'), str_starts_with($r, '235') ? '235 prihlaseni OK' : $r);
            }

            fwrite($fp, "QUIT\r\n");
            fclose($fp);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $ok = $error === null && $steps !== [] && array_reduce($steps, static fn (bool $c, array $s): bool => $c && $s['ok'], true);

        return [
            'ok' => $ok,
            'config' => [
                'host' => $host,
                'port' => $port,
                'starttls' => $startTls,
                'user' => $user !== '' ? '(nastaven)' : '(prazdny)',
            ],
            'steps' => $steps,
            'error' => $error,
        ];
    }

    /** @param resource $fp */
    private static function readAll($fp): string
    {
        $out = [];
        $deadline = microtime(true) + 6;
        while (microtime(true) < $deadline) {
            $line = fgets($fp, 515);
            if ($line === false || $line === '') {
                break;
            }
            $out[] = trim($line);
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return implode(' | ', $out);
    }

    /** Odeslani pres PHP mail() - lokalni MTA hostingu (wedos). */
    private static function mailSend(Config $cfg, string $to, string $subject, string $body): void
    {
        $from = (string) $cfg->get('SMTP_FROM', 'vyzkum@zootabor.eu');
        $fromName = (string) $cfg->get('SMTP_FROM_NAME', 'Vyzkum Zoo Tabor');

        $headers = [
            'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $from . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $body = str_replace(["\r\n", "\r"], "\n", $body);

        // -f nastavi obalku odesilatele (SPF). Doména je hostovana na uctu, takze povoleno.
        $ok = @mail($to, $encodedSubject, $body, implode("\r\n", $headers), '-f' . $from);
        if ($ok !== true) {
            throw new \RuntimeException('PHP mail() vratila false (MTA odmitlo nebo neni k dispozici).');
        }
    }

    private static function smtpSend(Config $cfg, string $to, string $subject, string $body): void
    {
        $host = (string) $cfg->get('SMTP_HOST', '');
        $port = (int) ($cfg->get('SMTP_PORT', 25) ?: 25);
        $user = (string) $cfg->get('SMTP_USER', '');
        $pass = (string) $cfg->get('SMTP_PASS', '');
        $startTls = (bool) $cfg->get('SMTP_USE_STARTTLS', true);
        $from = (string) $cfg->get('SMTP_FROM', 'vyzkum@zootabor.eu');
        $fromName = (string) $cfg->get('SMTP_FROM_NAME', 'Vyzkum Zoo Tabor');
        $ehlo = parse_url((string) $cfg->get('APP_URL', 'localhost'), PHP_URL_HOST) ?: 'localhost';

        if ($host === '') {
            throw new \RuntimeException('SMTP_HOST neni nastaven.');
        }

        $fp = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 15);
        if ($fp === false) {
            throw new \RuntimeException("SMTP pripojeni selhalo: {$errstr} ({$errno})");
        }
        stream_set_timeout($fp, 15);

        self::expect($fp, '220');
        self::cmd($fp, "EHLO {$ehlo}");
        self::readMultiline($fp, '250');

        if ($startTls) {
            self::cmd($fp, 'STARTTLS');
            self::expect($fp, '220');
            $crypto = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $crypto |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (!stream_socket_enable_crypto($fp, true, $crypto)) {
                throw new \RuntimeException('STARTTLS se nepodarilo navazat.');
            }
            self::cmd($fp, "EHLO {$ehlo}");
            self::readMultiline($fp, '250');
        }

        if ($user !== '') {
            self::cmd($fp, 'AUTH LOGIN');
            self::expect($fp, '334');
            self::cmd($fp, base64_encode($user));
            self::expect($fp, '334');
            self::cmd($fp, base64_encode($pass));
            self::expect($fp, '235');
        }

        self::cmd($fp, "MAIL FROM:<{$from}>");
        self::expect($fp, '250');
        self::cmd($fp, "RCPT TO:<{$to}>");
        self::expect($fp, '250');
        self::cmd($fp, 'DATA');
        self::expect($fp, '354');

        fwrite($fp, self::buildMessage($from, $fromName, $to, $subject, $body) . "\r\n.\r\n");
        self::expect($fp, '250');

        self::cmd($fp, 'QUIT');
        fclose($fp);
    }

    /** @param resource $fp */
    private static function cmd($fp, string $line): void
    {
        fwrite($fp, $line . "\r\n");
    }

    /** @param resource $fp */
    private static function expect($fp, string $code): void
    {
        $line = (string) fgets($fp, 515);
        if (strncmp($line, $code, 3) !== 0) {
            throw new \RuntimeException("SMTP: ocekavano {$code}, server vratil: " . trim($line));
        }
    }

    /** @param resource $fp Read a multiline reply (e.g. EHLO) until the final line. */
    private static function readMultiline($fp, string $code): void
    {
        do {
            $line = (string) fgets($fp, 515);
            if ($line === '' || strncmp($line, $code, 3) !== 0) {
                throw new \RuntimeException("SMTP: ocekavano {$code}, server vratil: " . trim($line));
            }
            $continued = isset($line[3]) && $line[3] === '-';
        } while ($continued);
    }

    private static function buildMessage(string $from, string $fromName, string $to, string $subject, string $body): string
    {
        $encodedName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $headers = [
            "From: {$encodedName} <{$from}>",
            "To: <{$to}>",
            "Subject: {$encodedSubject}",
            'Date: ' . date('r'),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ];

        $body = str_replace(["\r\n", "\r", "\n"], "\n", $body);
        $body = str_replace("\n", "\r\n", $body);
        // dot-stuffing
        $body = preg_replace('/^\./m', '..', $body) ?? $body;

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private static function record(string $to, string $subject, string $template, string $status, ?string $error): void
    {
        try {
            $stmt = Database::pdo()->prepare(
                'INSERT INTO email_log (recipient, subject, template, status, error)
                 VALUES (:r, :s, :t, :st, :e)'
            );
            $stmt->execute(['r' => $to, 's' => $subject, 't' => $template, 'st' => $status, 'e' => $error]);
        } catch (\Throwable $e) {
            self::logFile('[email_log selhal] ' . $e->getMessage() . "\n");
        }
    }

    private static function logFile(string $line): void
    {
        if (defined('STORAGE_PATH')) {
            @file_put_contents(STORAGE_PATH . '/logs/mail.log', '[' . date('Y-m-d H:i:s') . '] ' . $line, FILE_APPEND);
        }
    }
}
