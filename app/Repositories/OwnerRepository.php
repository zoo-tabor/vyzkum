<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/** Domenovi majitele psu (tabulka `owners`), ne aplikacni `users`. */
final class OwnerRepository
{
    private function pdo(): PDO
    {
        return Database::pdo();
    }

    /** @param array<string, mixed> $d */
    public function create(array $d): int
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO owners (display_name, first_name, last_name, address, preferred_contact_method, contact_consent, note)
             VALUES (:display_name, :first_name, :last_name, :address, :pcm, :consent, :note)'
        );
        $stmt->execute([
            'display_name' => $d['display_name'],
            'first_name' => $d['first_name'] ?? null,
            'last_name' => $d['last_name'] ?? null,
            'address' => $d['address'] ?? null,
            'pcm' => $d['preferred_contact_method'] ?? 'email',
            'consent' => !empty($d['contact_consent']) ? 1 : 0,
            'note' => $d['note'] ?? null,
        ]);
        return (int) $this->pdo()->lastInsertId();
    }

    public function addEmail(int $ownerId, string $email, bool $isPrimary = false): void
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO owner_emails (owner_id, email, is_primary) VALUES (:o, :e, :p)'
        );
        $stmt->execute(['o' => $ownerId, 'e' => $email, 'p' => $isPrimary ? 1 : 0]);
    }

    public function addPhone(int $ownerId, string $phone, ?string $label = null, bool $isPrimary = false): void
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO owner_phones (owner_id, phone, label, is_primary) VALUES (:o, :ph, :l, :p)'
        );
        $stmt->execute(['o' => $ownerId, 'ph' => $phone, 'l' => $label, 'p' => $isPrimary ? 1 : 0]);
    }

    public function findByPrimaryEmail(string $email): ?int
    {
        $stmt = $this->pdo()->prepare(
            'SELECT o.id FROM owners o
             JOIN owner_emails e ON e.owner_id = o.id
             WHERE e.is_primary = 1 AND e.email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM owners WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM owners WHERE user_id = :u LIMIT 1');
        $stmt->execute(['u' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function linkUser(int $ownerId, int $userId): void
    {
        $stmt = $this->pdo()->prepare('UPDATE owners SET user_id = :u, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['u' => $userId, 'id' => $ownerId]);
    }

    public function primaryEmail(int $ownerId): ?string
    {
        $stmt = $this->pdo()->prepare(
            'SELECT email FROM owner_emails WHERE owner_id = :o AND is_primary = 1 LIMIT 1'
        );
        $stmt->execute(['o' => $ownerId]);
        $email = $stmt->fetchColumn();
        return $email === false ? null : (string) $email;
    }

    /** @return array<int, array<string, mixed>> */
    public function emails(int $ownerId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM owner_emails WHERE owner_id = :o ORDER BY is_primary DESC, id ASC');
        $stmt->execute(['o' => $ownerId]);
        return $stmt->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function phones(int $ownerId): array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM owner_phones WHERE owner_id = :o ORDER BY is_primary DESC, id ASC');
        $stmt->execute(['o' => $ownerId]);
        return $stmt->fetchAll();
    }

    public function count(string $search = ''): int
    {
        if ($search === '') {
            return (int) $this->pdo()->query('SELECT COUNT(*) FROM owners')->fetchColumn();
        }
        $stmt = $this->pdo()->prepare('SELECT COUNT(*) FROM owners WHERE display_name LIKE :like');
        $stmt->execute(['like' => '%' . $search . '%']);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<int, array<string, mixed>> */
    public function paginate(string $search, int $limit, int $offset): array
    {
        $where = '1=1';
        $params = [];
        if ($search !== '') {
            $where = 'o.display_name LIKE :like';
            $params['like'] = '%' . $search . '%';
        }

        $limit = max(1, $limit);
        $offset = max(0, $offset);

        $stmt = $this->pdo()->prepare(
            "SELECT o.id, o.display_name, o.preferred_contact_method, o.note,
                    (SELECT email FROM owner_emails e WHERE e.owner_id = o.id AND e.is_primary = 1 LIMIT 1) AS primary_email,
                    (SELECT phone FROM owner_phones p WHERE p.owner_id = o.id ORDER BY p.is_primary DESC, p.id ASC LIMIT 1) AS primary_phone,
                    (SELECT COUNT(*) FROM dog_owners do2 WHERE do2.owner_id = o.id AND do2.is_current = 1) AS dog_count,
                    GREATEST(
                        COALESCE(o.updated_at, o.created_at),
                        COALESCE((SELECT MAX(m.created_at) FROM messages m WHERE m.sender_user_id = o.user_id), '1000-01-01'),
                        COALESCE((SELECT MAX(r.submitted_at) FROM form_responses r WHERE r.owner_id = o.id), '1000-01-01'),
                        COALESCE((SELECT MAX(do3.confirmed_at) FROM dog_owners do3 WHERE do3.owner_id = o.id), '1000-01-01'),
                        COALESCE((SELECT u.last_login_at FROM users u WHERE u.id = o.user_id), '1000-01-01')
                    ) AS last_activity
             FROM owners o
             WHERE {$where}
             ORDER BY o.display_name ASC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** @param array<string, mixed> $d */
    public function update(int $id, array $d): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE owners SET display_name = :dn, first_name = :fn, last_name = :ln, address = :addr,
                    preferred_contact_method = :pcm, contact_consent = :consent, note = :note, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'dn' => $d['display_name'],
            'fn' => $d['first_name'] ?? null,
            'ln' => $d['last_name'] ?? null,
            'addr' => $d['address'] ?? null,
            'pcm' => $d['preferred_contact_method'] ?? 'email',
            'consent' => !empty($d['contact_consent']) ? 1 : 0,
            'note' => $d['note'] ?? null,
        ]);
    }

    public function setPrimaryEmail(int $ownerId, string $email): void
    {
        $del = $this->pdo()->prepare('DELETE FROM owner_emails WHERE owner_id = :o AND is_primary = 1');
        $del->execute(['o' => $ownerId]);
        if ($email !== '') {
            $this->addEmail($ownerId, $email, true);
        }
    }

    /** @return array<int, array<string, mixed>> psi navazani na majitele */
    public function dogsOf(int $ownerId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT d.id, d.name, b.name AS breed_name, do2.is_current, do2.valid_from, do2.valid_to
             FROM dog_owners do2
             JOIN dogs d ON d.id = do2.dog_id
             JOIN breeds b ON b.id = d.breed_id
             WHERE do2.owner_id = :o
             ORDER BY do2.is_current DESC, d.name ASC'
        );
        $stmt->execute(['o' => $ownerId]);
        return $stmt->fetchAll();
    }

    /** Vztah majitel-pes existuje? currentOnly = jen aktualni vztah. */
    public function ownsDog(int $ownerId, int $dogId, bool $currentOnly = false): bool
    {
        $sql = 'SELECT 1 FROM dog_owners WHERE owner_id = :o AND dog_id = :d';
        if ($currentOnly) {
            $sql .= ' AND is_current = 1';
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute(['o' => $ownerId, 'd' => $dogId]);
        return (bool) $stmt->fetchColumn();
    }

    public function updateContactInfo(int $ownerId, ?string $address): void
    {
        $stmt = $this->pdo()->prepare('UPDATE owners SET address = :a, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['a' => $address, 'id' => $ownerId]);
    }

    public function setContactConsent(int $ownerId, bool $consent): void
    {
        $stmt = $this->pdo()->prepare('UPDATE owners SET contact_consent = :c, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['c' => $consent ? 1 : 0, 'id' => $ownerId]);
    }

    /** Oznaci onboarding majitele za dokonceny a ulozi souhlas s kontaktovanim. */
    public function markOnboarded(int $ownerId, bool $contactConsent): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE owners SET onboarding_completed_at = NOW(), contact_consent = :c, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['c' => $contactConsent ? 1 : 0, 'id' => $ownerId]);
    }

    /** @param array<int, string> $phones nahradi vsechny telefony */
    public function replacePhones(int $ownerId, array $phones): void
    {
        $del = $this->pdo()->prepare('DELETE FROM owner_phones WHERE owner_id = :o');
        $del->execute(['o' => $ownerId]);
        foreach ($phones as $phone) {
            $this->addPhone($ownerId, $phone, null, false);
        }
    }

    /** @param array<int, string> $emails nahradi sekundarni (ne-primarni) e-maily */
    public function replaceSecondaryEmails(int $ownerId, array $emails): void
    {
        $del = $this->pdo()->prepare('DELETE FROM owner_emails WHERE owner_id = :o AND is_primary = 0');
        $del->execute(['o' => $ownerId]);
        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addEmail($ownerId, $email, false);
            }
        }
    }

    /** @return array<int, array<string, mixed>> pro vyber pri rucnim prirazeni */
    public function allForSelect(int $limit = 500): array
    {
        $limit = max(1, $limit);
        return $this->pdo()->query(
            "SELECT id, display_name FROM owners ORDER BY display_name ASC LIMIT {$limit}"
        )->fetchAll();
    }
}
