<?php
declare(strict_types=1);

namespace App\Services;

/**
 * RFC 6238 TOTP (SHA1, 6 digits, 30s period) - pure PHP, no dependencies.
 * Used for the research_admin two-factor login.
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;
    private const DIGITS = 6;

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    public static function verify(string $secretBase32, string $code, int $window = 1, ?int $timestamp = null): bool
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== self::DIGITS) {
            return false;
        }

        $timestamp ??= time();
        $counter = intdiv($timestamp, self::PERIOD);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::hotp($secretBase32, $counter + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    /** Code valid at a given unix timestamp (used in tests). */
    public static function codeAt(string $secretBase32, int $timestamp): string
    {
        return self::hotp($secretBase32, intdiv($timestamp, self::PERIOD));
    }

    public static function provisioningUri(string $secretBase32, string $accountLabel, string $issuer): string
    {
        $label = rawurlencode($issuer . ':' . $accountLabel);
        $query = http_build_query([
            'secret' => $secretBase32,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        return 'otpauth://totp/' . $label . '?' . $query;
    }

    private static function hotp(string $secretBase32, int $counter): string
    {
        $key = self::base32Decode($secretBase32);
        $binCounter = pack('J', $counter); // 64-bit big-endian
        $hash = hash_hmac('sha1', $binCounter, $key, true);

        $offset = ord($hash[strlen($hash) - 1]) & 0x0f;
        $truncated = ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff);

        $otp = $truncated % (10 ** self::DIGITS);
        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    public static function base32Encode(string $data): string
    {
        if ($data === '') {
            return '';
        }

        $buffer = 0;
        $bitsLeft = 0;
        $out = '';

        foreach (str_split($data) as $char) {
            $buffer = ($buffer << 8) | ord($char);
            $bitsLeft += 8;
            while ($bitsLeft >= 5) {
                $bitsLeft -= 5;
                $out .= self::ALPHABET[($buffer >> $bitsLeft) & 0x1f];
            }
        }

        if ($bitsLeft > 0) {
            $out .= self::ALPHABET[($buffer << (5 - $bitsLeft)) & 0x1f];
        }

        return $out;
    }

    public static function base32Decode(string $secret): string
    {
        $secret = strtoupper((string) preg_replace('/[^A-Z2-7]/', '', $secret));
        if ($secret === '') {
            return '';
        }

        $buffer = 0;
        $bitsLeft = 0;
        $out = '';

        foreach (str_split($secret) as $char) {
            $buffer = ($buffer << 5) | strpos(self::ALPHABET, $char);
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $out .= chr(($buffer >> $bitsLeft) & 0xff);
            }
        }

        return $out;
    }
}
