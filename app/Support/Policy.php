<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Pure authorization helpers. Kept side-effect free so they are unit testable
 * without an HTTP request or database.
 */
final class Policy
{
    public const ROLES = ['research_admin', 'club_viewer', 'vet', 'owner'];

    /**
     * @param array<string, mixed>|null $user
     * @param string|array<int, string> $roles
     */
    public static function hasRole(?array $user, string|array $roles): bool
    {
        if ($user === null) {
            return false;
        }

        $allowed = is_array($roles) ? $roles : [$roles];
        return in_array($user['role'] ?? null, $allowed, true);
    }

    /** research_admin sees every breed; everyone else is scoped. */
    public static function canSeeAllBreeds(?array $user): bool
    {
        return self::hasRole($user, 'research_admin');
    }

    /**
     * @param array<string, mixed>|null $user
     * @param array<int, int> $accessibleBreedIds breed ids the user may access
     */
    public static function canAccessBreed(?array $user, ?int $breedId, array $accessibleBreedIds): bool
    {
        if (self::canSeeAllBreeds($user)) {
            return true;
        }
        if ($breedId === null) {
            return false;
        }
        return in_array($breedId, $accessibleBreedIds, true);
    }
}
