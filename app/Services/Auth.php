<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\UserRepository;

final class Auth
{
    /** @var array<string, mixed>|null per-request cache of the current user */
    private static ?array $cachedUser = null;
    private static bool $loaded = false;

    /** Preferred password hashing algorithm (Argon2id when available). */
    public static function algo(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, self::algo());
    }

    /**
     * Verify credentials. Returns the user row on success, null otherwise.
     * Does NOT establish a session - call login() for that.
     *
     * @return array<string, mixed>|null
     */
    public static function attempt(string $email, string $password): ?array
    {
        $repo = new UserRepository();
        $user = $repo->findByEmail($email);

        if ($user === null || ($user['status'] ?? '') !== 'active' || empty($user['password_hash'])) {
            // Equalize timing a little so missing accounts are harder to probe.
            password_verify($password, '$2y$12$usesomesillystringforsalt0000000000000000000000000000');
            return null;
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            return null;
        }

        if (password_needs_rehash((string) $user['password_hash'], self::algo())) {
            $repo->updatePasswordHash((int) $user['id'], self::hash($password));
        }

        return $user;
    }

    /** @param array<string, mixed> $user */
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::put('user_id', (int) $user['id']);
        self::$cachedUser = $user;
        self::$loaded = true;
        (new UserRepository())->touchLastLogin((int) $user['id']);
    }

    public static function logout(): void
    {
        Session::invalidate();
        self::$cachedUser = null;
        self::$loaded = true;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user !== null ? (int) $user['id'] : null;
    }

    public static function role(): ?string
    {
        $user = self::user();
        return $user !== null ? (string) $user['role'] : null;
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$cachedUser;
        }

        self::$loaded = true;
        $id = Session::get('user_id');
        if (!is_int($id) && !ctype_digit((string) $id)) {
            return self::$cachedUser = null;
        }

        $user = (new UserRepository())->findById((int) $id);
        if ($user === null || ($user['status'] ?? '') !== 'active') {
            Session::forget('user_id');
            return self::$cachedUser = null;
        }

        return self::$cachedUser = $user;
    }

    /** Test seam: reset the per-request cache. */
    public static function flush(): void
    {
        self::$cachedUser = null;
        self::$loaded = false;
    }
}
