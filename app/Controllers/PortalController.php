<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\DogRepository;
use App\Repositories\FilesRepository;
use App\Repositories\FormRepository;
use App\Repositories\FormResponseRepository;
use App\Repositories\MessageRepository;
use App\Repositories\OwnerRepository;
use App\Repositories\TransferRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\OwnershipTransferService;
use App\Support\Dates;
use App\Support\FileStorage;
use App\Support\FormConditions;

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
        $messages = new MessageRepository();
        $thread = $messages->dogThread((int) $id);

        return view('portal/dog', [
            'title' => $dog['name'],
            'owner' => $owner,
            'dog' => $dog,
            'relation' => $dogs->relation((int) $id, (int) $owner['id']),
            'documents' => $dogs->healthDocuments((int) $id),
            'forms' => (new FormRepository())->publishedFormsForBreed((int) $dog['breed_id']),
            'responses' => (new FormResponseRepository())->responsesForDog((int) $id),
            'messages' => $thread !== null ? $messages->messages((int) $thread['id']) : [],
            'pendingTransfer' => (new TransferRepository())->pendingForDog((int) $id),
            'notice' => Session::flash('portal_notice'),
            'error' => Session::flash('portal_error'),
        ]);
    }

    public function sendMessage(string $id): string
    {
        Csrf::verify();
        [$owner, $dog] = $this->ownerAndDog((int) $id, true);
        if ($dog === null) {
            Session::flash('portal_error', 'K tomuto psovi nemate pristup.');
            redirect('/portal');
        }
        $body = trim((string) input('body'));
        if ($body === '') {
            Session::flash('portal_error', 'Zprava nesmi byt prazdna.');
            redirect('/portal/dogs/' . $id);
        }

        $messages = new MessageRepository();
        $threadId = $messages->findOrCreateDogThread((int) $id, Auth::id());
        $messages->addMessage($threadId, Auth::id(), 'owner', $body, 'open');
        AuditService::log(Auth::id(), 'owner', 'message_sent', 'dog', $id);
        Session::flash('portal_notice', 'Zprava odeslana vyzkumnemu tymu.');
        redirect('/portal/dogs/' . $id);
    }

    public function transferRequest(string $id): string
    {
        Csrf::verify();
        [$owner, $dog] = $this->ownerAndDog((int) $id, true);
        if ($dog === null) {
            Session::flash('portal_error', 'K tomuto psovi nemate pristup.');
            redirect('/portal');
        }

        $name = trim((string) input('new_owner_name'));
        $email = trim((string) input('new_owner_email'));
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('portal_error', 'Zadejte jmeno a platny e-mail noveho majitele.');
            redirect('/portal/dogs/' . $id);
        }

        $result = (new OwnershipTransferService())->request((int) $id, (int) $owner['id'], $name, $email, Auth::id());
        AuditService::log(Auth::id(), 'owner', 'ownership_transfer_requested', 'dog', $id, null, ['new_owner_email' => strtolower($email)]);
        Session::flash($result['ok'] ? 'portal_notice' : 'portal_error', $result['message']);
        redirect('/portal/dogs/' . $id);
    }

    public function fillForm(string $id, string $defId): string
    {
        [$owner, $dog] = $this->ownerAndDog((int) $id, true);
        if ($dog === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Pes nenalezen']);
        }

        $forms = new FormRepository();
        $def = $forms->findDefinition((int) $defId);
        $version = $forms->publishedVersion((int) $defId);
        if ($def === null || $version === null || (int) $def['breed_id'] !== (int) $dog['breed_id']) {
            Session::flash('portal_error', 'Dotaznik neni dostupny.');
            redirect('/portal/dogs/' . $id);
        }

        return view('portal/form', [
            'title' => $def['name'],
            'dog' => $dog,
            'def' => $def,
            'questions' => $forms->questions((int) $version['id']),
            'options' => $forms->optionsByQuestion((int) $version['id']),
            'error' => Session::flash('portal_error'),
        ]);
    }

    public function submitForm(string $id, string $defId): string
    {
        Csrf::verify();
        [$owner, $dog] = $this->ownerAndDog((int) $id, true);
        if ($dog === null) {
            Session::flash('portal_error', 'K tomuto psovi nemate pristup.');
            redirect('/portal');
        }

        $forms = new FormRepository();
        $def = $forms->findDefinition((int) $defId);
        $version = $forms->publishedVersion((int) $defId);
        if ($def === null || $version === null || (int) $def['breed_id'] !== (int) $dog['breed_id']) {
            Session::flash('portal_error', 'Dotaznik neni dostupny.');
            redirect('/portal/dogs/' . $id);
        }

        $questions = $forms->questions((int) $version['id']);
        $optionsByQ = $forms->optionsByQuestion((int) $version['id']);

        // 1) Mapa odpovedi podle klice (pro vyhodnoceni podminek).
        $answersByKey = [];
        foreach ($questions as $q) {
            $field = 'q_' . (int) $q['id'];
            $answersByKey[$q['question_key']] = $q['type'] === 'multiple_choice'
                ? (array) ($_POST[$field] ?? [])
                : (string) input($field);
        }

        // 2) Vestaveny blok "je pes naziva? -> datum umrti" -> propsani do psa.
        $alive = (string) input('builtin_alive') !== 'no';
        $deathIso = $alive ? null : Dates::fromCz((string) input('builtin_death_date'));
        if (!$alive && $deathIso === null) {
            Session::flash('portal_error', 'Zadejte platne datum umrti (DD.MM.RRRR).');
            redirect('/portal/dogs/' . $id . '/forms/' . $defId);
        }
        $dogsRepo = new DogRepository();
        $dogsRepo->setAliveStatus((int) $id, (int) $owner['id'], $alive, $deathIso, null);

        // 3) Ulozeni odpovedi.
        $responses = new FormResponseRepository();
        $responseId = $responses->create((int) $version['id'], (int) $id, (int) $owner['id'], Auth::id(), trim((string) input('note')) ?: null);

        foreach ($questions as $q) {
            $config = !empty($q['config_json']) ? (json_decode((string) $q['config_json'], true) ?: []) : [];
            if (!FormConditions::isVisible($config, $answersByKey)) {
                continue;
            }
            $this->storeAnswer($responses, $responseId, $q, $optionsByQ[(int) $q['id']] ?? [], (string) $dog['breed_slug'], (int) $owner['id'], (int) $id);
        }

        AuditService::log(Auth::id(), 'owner', 'form_submitted', 'form_response', (string) $responseId, null, ['dog_id' => (int) $id]);
        Session::flash('portal_notice', 'Dekujeme, dotaznik byl odeslan.');
        redirect('/portal/dogs/' . $id);
    }

    /**
     * @param array<string, mixed> $q
     * @param array<int, array<string, mixed>> $options
     */
    private function storeAnswer(FormResponseRepository $responses, int $responseId, array $q, array $options, string $breedSlug, int $ownerId, int $dogId): void
    {
        $field = 'q_' . (int) $q['id'];
        $type = (string) $q['type'];

        switch ($type) {
            case 'multiple_choice':
                $selected = array_map('strval', (array) ($_POST[$field] ?? []));
                if ($selected === []) {
                    return;
                }
                $labels = [];
                foreach ($options as $o) {
                    if (in_array((string) $o['option_key'], $selected, true)) {
                        $labels[] = $o['label'];
                    }
                }
                $responses->addAnswer($responseId, (int) $q['id'], ['text' => implode(', ', $labels), 'json' => $selected]);
                break;

            case 'single_choice':
                $key = (string) input($field);
                if ($key === '') {
                    return;
                }
                foreach ($options as $o) {
                    if ((string) $o['option_key'] === $key) {
                        $responses->addAnswer($responseId, (int) $q['id'], ['text' => $o['label'], 'option_id' => (int) $o['id'], 'json' => [$key]]);
                        return;
                    }
                }
                break;

            case 'yes_no':
                $val = (string) input($field);
                if ($val === '') {
                    return;
                }
                $responses->addAnswer($responseId, (int) $q['id'], ['text' => $val === 'yes' ? 'Ano' : 'Ne']);
                break;

            case 'number':
                $val = trim((string) input($field));
                if ($val === '') {
                    return;
                }
                $responses->addAnswer($responseId, (int) $q['id'], ['text' => $val, 'number' => (float) str_replace(',', '.', $val)]);
                break;

            case 'date':
                $val = trim((string) input($field)); // <input type=date> -> YYYY-MM-DD
                if ($val === '' || !\App\Services\DogOwnerImporter::isDate($val)) {
                    return;
                }
                $responses->addAnswer($responseId, (int) $q['id'], ['text' => Dates::toCz($val), 'date' => $val]);
                break;

            case 'file':
                if (($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    return;
                }
                try {
                    $stored = FileStorage::store($_FILES[$field], $breedSlug, $ownerId, $dogId, 'form');
                    $fileId = (new FilesRepository())->create('dog', $dogId, $stored['original'], $stored['relative'], $stored['mime'], $stored['size'], Auth::id());
                    $responses->addAnswer($responseId, (int) $q['id'], ['text' => $stored['original'], 'json' => ['file_id' => $fileId]]);
                } catch (\Throwable $e) {
                    // tichy preskok jednoho souboru, zbytek dotazniku se ulozi
                }
                break;

            default: // short_text, long_text
                $val = trim((string) input($field));
                if ($val !== '') {
                    $responses->addAnswer($responseId, (int) $q['id'], ['text' => $val]);
                }
        }
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
