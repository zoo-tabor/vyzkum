<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\DogRepository;
use App\Repositories\FilesRepository;
use App\Repositories\OwnerRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Support\Dates;
use App\Support\FileStorage;

final class PortalController
{
    public function index(): string
    {
        $repo = new OwnerRepository();
        $owner = $repo->findByUserId((int) Auth::id());

        return view('portal/dogs', [
            'title' => 'Moji psi',
            'owner' => $owner,
            'dogs' => $owner !== null ? $repo->dogsOf((int) $owner['id']) : [],
            'emails' => $owner !== null ? $repo->emails((int) $owner['id']) : [],
            'phones' => $owner !== null ? $repo->phones((int) $owner['id']) : [],
            'notice' => Session::flash('portal_notice'),
            'error' => Session::flash('portal_error'),
        ]);
    }

    public function dog(string $id): string
    {
        [$owner, $dog] = $this->ownerAndDog((int) $id, false);
        if ($dog === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Pes nenalezen']);
        }

        $dogs = new DogRepository();
        return view('portal/dog', [
            'title' => $dog['name'],
            'owner' => $owner,
            'dog' => $dog,
            'relation' => $dogs->relation((int) $id, (int) $owner['id']),
            'documents' => $dogs->healthDocuments((int) $id),
            'notice' => Session::flash('portal_notice'),
            'error' => Session::flash('portal_error'),
        ]);
    }

    public function confirm(string $id): string
    {
        Csrf::verify();
        [$owner, $dog] = $this->ownerAndDog((int) $id, true);
        if ($dog === null) {
            Session::flash('portal_error', 'K tomuto psovi nemate pristup.');
            redirect('/portal');
        }

        (new DogRepository())->confirmOwnership((int) $id, (int) $owner['id']);
        AuditService::log(Auth::id(), 'owner', 'dog_confirmed', 'dog', $id);
        Session::flash('portal_notice', 'Potvrdili jste, ze pes je stale vas. Dekujeme.');
        redirect('/portal/dogs/' . $id);
    }

    public function death(string $id): string
    {
        Csrf::verify();
        [$owner, $dog] = $this->ownerAndDog((int) $id, true);
        if ($dog === null) {
            Session::flash('portal_error', 'K tomuto psovi nemate pristup.');
            redirect('/portal');
        }

        $alive = (string) input('alive') === 'yes';
        $note = trim((string) input('note')) ?: null;

        if (!$alive) {
            $deathIso = Dates::fromCz((string) input('death_date'));
            if ($deathIso === null) {
                Session::flash('portal_error', 'Zadejte platne datum umrti ve formatu DD.MM.RRRR.');
                redirect('/portal/dogs/' . $id);
            }
            (new DogRepository())->setAliveStatus((int) $id, (int) $owner['id'], false, $deathIso, $note);
            AuditService::log(Auth::id(), 'owner', 'dog_death_reported', 'dog', $id, null, ['death_date' => $deathIso]);
            Session::flash('portal_notice', 'Dekujeme, zaznamenali jsme datum umrti.');
        } else {
            (new DogRepository())->setAliveStatus((int) $id, (int) $owner['id'], true, null, $note);
            AuditService::log(Auth::id(), 'owner', 'dog_alive_confirmed', 'dog', $id);
            Session::flash('portal_notice', 'Dekujeme za potvrzeni, ze pes zije.');
        }
        redirect('/portal/dogs/' . $id);
    }

    public function uploadDocument(string $id): string
    {
        Csrf::verify();
        [$owner, $dog] = $this->ownerAndDog((int) $id, true);
        if ($dog === null) {
            Session::flash('portal_error', 'K tomuto psovi nemate pristup.');
            redirect('/portal');
        }

        try {
            $stored = FileStorage::store($_FILES['document'] ?? [], (string) $dog['breed_slug'], (int) $owner['id'], (int) $id, 'health');
            $fileId = (new FilesRepository())->create('dog', (int) $id, $stored['original'], $stored['relative'], $stored['mime'], $stored['size'], Auth::id());
            (new DogRepository())->addHealthDocument((int) $id, (int) $owner['id'], $fileId, trim((string) input('document_type')) ?: null, Dates::fromCz((string) input('document_date')), trim((string) input('note')) ?: null);
            AuditService::log(Auth::id(), 'owner', 'health_document_uploaded', 'dog', $id, null, ['file_id' => $fileId]);
            Session::flash('portal_notice', 'Dokument byl nahran.');
        } catch (\Throwable $e) {
            Session::flash('portal_error', 'Nahrani se nepodarilo: ' . $e->getMessage());
        }
        redirect('/portal/dogs/' . $id);
    }

    public function contacts(): string
    {
        $repo = new OwnerRepository();
        $owner = $repo->findByUserId((int) Auth::id());
        if ($owner === null) {
            redirect('/portal');
        }

        return view('portal/contacts', [
            'title' => 'Moje kontaktni udaje',
            'owner' => $owner,
            'primaryEmail' => $repo->primaryEmail((int) $owner['id']),
            'secondaryEmails' => array_values(array_filter($repo->emails((int) $owner['id']), static fn ($e) => (int) $e['is_primary'] === 0)),
            'phones' => $repo->phones((int) $owner['id']),
            'notice' => Session::flash('portal_notice'),
            'error' => Session::flash('portal_error'),
        ]);
    }

    public function updateContacts(): string
    {
        Csrf::verify();
        $repo = new OwnerRepository();
        $owner = $repo->findByUserId((int) Auth::id());
        if ($owner === null) {
            redirect('/portal');
        }

        $repo->updateContactInfo((int) $owner['id'], trim((string) input('address')) ?: null);
        $repo->replacePhones((int) $owner['id'], $this->splitList((string) input('phones')));
        $repo->replaceSecondaryEmails((int) $owner['id'], $this->splitList((string) input('secondary_emails')));

        AuditService::log(Auth::id(), 'owner', 'owner_contacts_updated', 'owner', (string) $owner['id']);
        Session::flash('portal_notice', 'Kontaktni udaje byly ulozeny.');
        redirect('/portal/contacts');
    }

    /**
     * @return array{0: array<string,mixed>|null, 1: array<string,mixed>|null}
     */
    private function ownerAndDog(int $dogId, bool $currentOnly): array
    {
        $repo = new OwnerRepository();
        $owner = $repo->findByUserId((int) Auth::id());
        if ($owner === null || !$repo->ownsDog((int) $owner['id'], $dogId, $currentOnly)) {
            return [$owner, null];
        }
        $dog = (new DogRepository())->find($dogId);
        return [$owner, $dog];
    }

    /** @return array<int, string> */
    private function splitList(string $raw): array
    {
        $parts = array_map('trim', explode(';', $raw));
        return array_values(array_filter($parts, static fn (string $s): bool => $s !== ''));
    }
}
