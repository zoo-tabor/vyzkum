<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;

/**
 * Holds the admin "current breed" selection in the session. null = all breeds.
 * Every domain query must apply this filter unless the user may see all breeds.
 */
final class BreedContext
{
    private const KEY = '_breed_id';
    private const RECENT_KEY = '_breed_recent';
    private const RECENT_MAX = 5;

    public static function current(): ?int
    {
        $value = Session::get(self::KEY);
        return is_int($value) ? $value : null;
    }

    public static function isAll(): bool
    {
        return self::current() === null;
    }

    public static function set(?int $breedId): void
    {
        if ($breedId === null) {
            Session::forget(self::KEY);
            return;
        }

        Session::put(self::KEY, $breedId);
        self::pushRecent($breedId);
    }

    /** @return array<int, int> most-recently selected breed ids */
    public static function recent(): array
    {
        $recent = Session::get(self::RECENT_KEY, []);
        return is_array($recent) ? array_values(array_filter($recent, 'is_int')) : [];
    }

    private static function pushRecent(int $breedId): void
    {
        $recent = self::recent();
        $recent = array_values(array_filter($recent, static fn (int $id): bool => $id !== $breedId));
        array_unshift($recent, $breedId);
        Session::put(self::RECENT_KEY, array_slice($recent, 0, self::RECENT_MAX));
    }
}
