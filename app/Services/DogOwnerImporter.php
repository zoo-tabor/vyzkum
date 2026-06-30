<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\BreedRepository;
use App\Repositories\DogRepository;
use App\Repositories\OwnerRepository;
use App\Repositories\SampleRepository;

/**
 * Import psu + majitelu + vazeb z CSV (sablona import_sablona_psi_majitele_vzorky).
 * Sample_* sloupce se zatim ignoruji (modul vzorku az Faze 4); sample_received_at
 * se uklada na psa.
 */
final class DogOwnerImporter
{
    private int $ownersCreated = 0;
    private int $ownersReused = 0;

    public function __construct(
        private BreedRepository $breeds = new BreedRepository(),
        private DogRepository $dogs = new DogRepository(),
        private OwnerRepository $owners = new OwnerRepository(),
    ) {
    }

    public static function normalizeSex(string $value): string
    {
        return match (strtolower(trim($value))) {
            'm', 'male', 'pes', 'samec' => 'male',
            'f', 'female', 'fena', 'samice' => 'female',
            default => 'unknown',
        };
    }

    public static function isDate(string $value): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return $d !== false && $d->format('Y-m-d') === $value;
    }

    /**
     * Validate parsed rows. No DB writes.
     *
     * @param array<int, array<string, string>> $rows
     * @return array{rows: array<int, array{line:int, data:array<string,string>, errors:array<int,string>}>,
     *               summary: array{total:int, valid:int, invalid:int}}
     */
    public function preview(array $rows): array
    {
        $breedBySlug = $this->breedMap();
        $seenChips = [];
        $seenPedigrees = [];

        $out = [];
        $valid = 0;
        $invalid = 0;
        $line = 1; // header is line 1

        foreach ($rows as $r) {
            $line++;
            $errors = [];

            $slug = strtolower(trim($r['breed_slug'] ?? ''));
            if ($slug === '') {
                $errors[] = 'chybí breed_slug';
            } elseif (!isset($breedBySlug[$slug])) {
                $errors[] = "plemeno '{$slug}' neexistuje (založte ho v Plemena)";
            }

            if (trim($r['dog_name'] ?? '') === '') {
                $errors[] = 'chybí dog_name';
            }

            foreach (['birth_date', 'death_date', 'sample_received_at'] as $df) {
                $dv = trim($r[$df] ?? '');
                if ($dv !== '' && !self::isDate($dv)) {
                    $errors[] = "{$df} není ve formátu YYYY-MM-DD";
                }
            }

            $email = strtolower(trim($r['owner_primary_email'] ?? ''));
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'neplatný owner_primary_email';
            }

            $chip = trim($r['chip_number'] ?? '');
            if ($chip !== '') {
                if (isset($seenChips[$chip])) {
                    $errors[] = "duplicitní čip v souboru ({$chip})";
                } elseif ($this->dogs->chipExists($chip)) {
                    $errors[] = "čip už existuje v databázi ({$chip})";
                }
                $seenChips[$chip] = true;
            }

            $pedigree = trim($r['pedigree_number'] ?? '');
            if ($pedigree !== '') {
                if (isset($seenPedigrees[$pedigree])) {
                    $errors[] = "duplicitní číslo průkazu v souboru ({$pedigree})";
                } elseif ($this->dogs->pedigreeExists($pedigree)) {
                    $errors[] = "číslo průkazu už existuje v databázi ({$pedigree})";
                }
                $seenPedigrees[$pedigree] = true;
            }

            $out[] = ['line' => $line, 'data' => $r, 'errors' => $errors];
            $errors === [] ? $valid++ : $invalid++;
        }

        return [
            'rows' => $out,
            'summary' => ['total' => count($rows), 'valid' => $valid, 'invalid' => $invalid],
        ];
    }

    /**
     * Insert all valid rows in a single transaction.
     *
     * @param array<int, array<string, string>> $rows
     * @return array{dogs:int, owners_created:int, owners_reused:int, skipped:int}
     */
    public function commit(array $rows, int $userId): array
    {
        $preview = $this->preview($rows);
        $breedBySlug = $this->breedMap();

        $this->ownersCreated = 0;
        $this->ownersReused = 0;
        $dogsCreated = 0;
        $skipped = 0;

        $ownerByEmail = [];
        $ownerByNamePhone = [];

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            foreach ($preview['rows'] as $row) {
                if ($row['errors'] !== []) {
                    $skipped++;
                    continue;
                }
                $r = $row['data'];
                $breedId = $breedBySlug[strtolower(trim($r['breed_slug']))];

                $dogId = $this->dogs->create([
                    'breed_id' => $breedId,
                    'name' => trim($r['dog_name']),
                    'kennel_name' => $r['kennel_name'] ?? null,
                    'chip_number' => $r['chip_number'] ?? null,
                    'pedigree_number' => $r['pedigree_number'] ?? null,
                    'sex' => self::normalizeSex($r['sex'] ?? ''),
                    'birth_date' => $r['birth_date'] ?? null,
                    'death_date' => $r['death_date'] ?? null,
                    'death_cause' => $r['death_cause'] ?? null,
                    'color' => $r['color'] ?? null,
                    'test_group' => $r['test_group'] ?? null,
                    'health_summary' => $r['health_summary'] ?? null,
                    'sample_received_at' => $r['sample_received_at'] ?? null,
                    'status' => 'active',
                ]);
                $dogsCreated++;

                $ownerId = $this->resolveOwner($r, $ownerByEmail, $ownerByNamePhone);
                if ($ownerId !== null) {
                    $this->dogs->linkOwner($dogId, $ownerId, 'import');
                }

                // Vzorek (sample_id) - aby na nej slo navazat geneticky import.
                $sampleId = trim((string) ($r['sample_id'] ?? ''));
                if ($sampleId !== '') {
                    (new SampleRepository())->ensureImportedSample($sampleId, $breedId, $dogId, trim((string) ($r['sample_received_at'] ?? '')) ?: null);
                }
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return [
            'dogs' => $dogsCreated,
            'owners_created' => $this->ownersCreated,
            'owners_reused' => $this->ownersReused,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param array<string, string> $r
     * @param array<string, int> $ownerByEmail
     * @param array<string, int> $ownerByNamePhone
     */
    private function resolveOwner(array $r, array &$ownerByEmail, array &$ownerByNamePhone): ?int
    {
        $email = strtolower(trim($r['owner_primary_email'] ?? ''));
        $name = trim($r['owner_name'] ?? '');
        $secondary = $this->splitList($r['owner_secondary_emails'] ?? '');
        $phones = $this->splitList($r['owner_phones'] ?? '');
        $address = trim($r['owner_address'] ?? '') ?: null;

        if ($email !== '') {
            if (isset($ownerByEmail[$email])) {
                $this->ownersReused++;
                return $ownerByEmail[$email];
            }
            $existing = $this->owners->findByPrimaryEmail($email);
            if ($existing !== null) {
                $ownerByEmail[$email] = $existing;
                $this->ownersReused++;
                return $existing;
            }
            $id = $this->createOwner($name !== '' ? $name : $email, $address, $email, $secondary, $phones);
            $ownerByEmail[$email] = $id;
            return $id;
        }

        if ($name === '') {
            return null; // no owner info on this row
        }

        $key = mb_strtolower($name) . '|' . ($phones[0] ?? '');
        if (isset($ownerByNamePhone[$key])) {
            $this->ownersReused++;
            return $ownerByNamePhone[$key];
        }
        $id = $this->createOwner($name, $address, null, $secondary, $phones);
        $ownerByNamePhone[$key] = $id;
        return $id;
    }

    /**
     * @param array<int, string> $secondaryEmails
     * @param array<int, string> $phones
     */
    private function createOwner(string $displayName, ?string $address, ?string $primaryEmail, array $secondaryEmails, array $phones): int
    {
        $id = $this->owners->create([
            'display_name' => $displayName,
            'address' => $address,
            'preferred_contact_method' => $primaryEmail !== null ? 'email' : 'phone',
        ]);
        if ($primaryEmail !== null) {
            $this->owners->addEmail($id, $primaryEmail, true);
        }
        foreach ($secondaryEmails as $e) {
            if (filter_var($e, FILTER_VALIDATE_EMAIL)) {
                $this->owners->addEmail($id, $e, false);
            }
        }
        foreach ($phones as $p) {
            $this->owners->addPhone($id, $p, null, false);
        }
        $this->ownersCreated++;
        return $id;
    }

    /** @return array<string, int> slug => breed_id */
    private function breedMap(): array
    {
        $map = [];
        foreach ($this->breeds->all(false) as $b) {
            $map[strtolower((string) $b['slug'])] = (int) $b['id'];
        }
        return $map;
    }

    /** @return array<int, string> */
    private function splitList(string $raw): array
    {
        $parts = array_map('trim', explode(';', $raw));
        return array_values(array_filter($parts, static fn (string $s): bool => $s !== ''));
    }
}
