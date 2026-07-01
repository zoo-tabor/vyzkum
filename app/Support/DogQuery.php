<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Pure builders for the dog list query. Column names come from a fixed
 * whitelist (never from user input) and values are returned as bound params,
 * so the output is safe to interpolate into SQL.
 */
final class DogQuery
{
    /** sort key => SQL column */
    public const SORTS = [
        'name' => 'd.name',
        'chip' => 'd.chip_number',
        'birth' => 'd.birth_date',
        'updated' => 'd.updated_at',
        'breed' => 'b.name',
    ];

    public static function orderBy(?string $sort, ?string $dir): string
    {
        $column = self::SORTS[$sort] ?? 'd.name';
        $direction = strtolower((string) $dir) === 'desc' ? 'DESC' : 'ASC';
        return $column . ' ' . $direction . ', d.id ' . $direction;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{where: string, params: array<string, mixed>}
     */
    public static function filters(array $filters, ?int $breedId): array
    {
        $where = [];
        $params = [];

        if ($breedId !== null) {
            $where[] = 'd.breed_id = :breed';
            $params['breed'] = $breedId;
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = 'd.name LIKE :q';
            $params['q'] = '%' . self::escapeLike($q) . '%';
        }

        $code = trim((string) ($filters['code'] ?? ''));
        if ($code !== '') {
            $where[] = '(d.chip_number LIKE :code OR d.pedigree_number LIKE :code)';
            $params['code'] = '%' . self::escapeLike($code) . '%';
        }

        $kennel = trim((string) ($filters['kennel'] ?? ''));
        if ($kennel !== '') {
            $where[] = 'd.kennel_name LIKE :kennel';
            $params['kennel'] = '%' . self::escapeLike($kennel) . '%';
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status === 'alive') {
            $where[] = 'd.death_date IS NULL';
        } elseif ($status === 'dead') {
            $where[] = 'd.death_date IS NOT NULL';
        }

        return [
            'where' => $where === [] ? '1=1' : implode(' AND ', $where),
            'params' => $params,
        ];
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
