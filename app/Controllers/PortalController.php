<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\DeathCauseRepository;
use App\Repositories\DogRepository;
use App\Repositories\FilesRepository;
use App\Repositories\FormAssignmentRepository;
use App\Repositories\FormRepository;
use App\Repositories\FormResponseRepository;
use App\Repositories\HealthEventRepository;
use App\Repositories\MessageRepository;
use App\Repositories\OwnerRepository;
use App\Repositories\TransferRepository;
use App\Repositories\UserRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\OwnerOnboardingService;
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
            'messageCount' => $thread !== null ? $messages->countMessages((int) $thread['id']) : 0,
            'causeTree' => (new DeathCauseRepository())->treeForBreed((int) $dog['breed_id']),
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
            Session::flash('portal_error', 'K tomuto psovi nemáte přístup.');
            redirect('/portal');
        }
        $body = trim((string) input('body'));
        if ($body === '') {
            Session::flash('portal_error', 'Zpráva nesmí být prázdná.');
            redirect('/portal/dogs/' . $id);
        }

        $messages = new MessageRepository();
        $threadId = $messages->findOrCreateDogThread((int) $id, Auth::id());
        $messages->addMessage($threadId, Auth::id(), 'owner', $body, 'open');
        AuditService::log(Auth::id(), 'owner', 'message_sent', 'dog', $id);
        Session::flash('portal_notice', 'Zpráva odeslána výzkumnému týmu.');
        redirect('/portal/dogs/' . $id);
    }

    public function transferRequest(string $id): string
    {
        Csrf::verify();
        [$owner, $dog] = $this->ownerAndDog((int) $id, true);
        if ($dog === null) {
            Session::flash('portal_error', 'K tomuto psovi nemáte přístup.');
            redirect('/portal');
        }

        $name = trim((string) input('new_owner_name'));
        $email = trim((string) input('new_owner_email'));
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Session::flash('portal_error', 'Zadejte jméno a platný e-mail nového majitele.');
            redirect('/portal/dogs/' . $id);
        }

        $result = (new OwnershipTransferService())->request((int) $id, (int) $owner['id'], $name, $email, trim((string) input('new_owner_phone')) ?: null, Auth::id());
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
            Session::flash('portal_error', 'Dotazník není dostupný.');
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
            Session::flash('portal_error', 'K tomuto psovi nemáte přístup.');
            redirect('/portal');
        }

        $forms = new FormRepository();
        $def = $forms->findDefinition((int) $defId);
        $version = $forms->publishedVersion((int) $defId);
        if ($def === null || $version === null || (int) $def['breed_id'] !== (int) $dog['breed_id']) {
            Session::flash('portal_error', 'Dotazník není dostupný.');
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
            Session::flash('portal_error', 'Zadejte platné datum úmrtí (DD.MM.RRRR).');
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
            $this->maybeHealthEvent($q, $config, $answersByKey, (int) $id, $dog['breed_id'] !== null ? (int) $dog['breed_id'] : null, $responseId);
        }

        // Pokud byl dotaznik rozeslan (existuje otevreny ukol), oznacime ho jako vyplneny.
        (new FormAssignmentRepository())->markCompleted((int) $defId, (int) $id, $responseId);

        AuditService::log(Auth::id(), 'owner', 'form_submitted', 'form_response', (string) $responseId, null, ['dog_id' => (int) $id]);
        Session::flash('portal_notice', 'Děkujeme, dotazník byl odeslán.');
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
            Session::flash('portal_error', 'K tomuto psovi nemáte přístup.');
            redirect('/portal');
        }

        (new DogRepository())->confirmOwnership((int) $id, (int) $owner['id']);
        AuditService::log(Auth::id(), 'owner', 'dog_confirmed', 'dog', $id);
        Session::flash('portal_notice', 'Potvrdili jste, že pes je stále váš. Děkujeme.');
        redirect('/portal/dogs/' . $id);
    }

    public function death(string $id): string
    {
        Csrf::verify();
        [$owner, $dog] = $this->ownerAndDog((int) $id, true);
        if ($dog === null) {
            Session::flash('portal_error', 'K tomuto psovi nemáte přístup.');
            redirect('/portal');
        }

        $alive = (string) input('alive') === 'yes';

        if (!$alive) {
            $deathIso = Dates::fromCz((string) input('death_date'));
            if ($deathIso === null) {
                Session::flash('portal_error', 'Zadejte platné datum úmrtí ve formátu DD.MM.RRRR.');
                redirect('/portal/dogs/' . $id);
            }

            // Pricina umrti: musi byt konecna polozka (list) z taxonomie plemene.
            $causeId = (int) input('death_cause_id');
            $leaf = (new DeathCauseRepository())->findLeaf($causeId, (int) $dog['breed_id']);
            if ($leaf === null) {
                Session::flash('portal_error', 'Vyberte prosím příčinu úmrtí ze seznamu.');
                redirect('/portal/dogs/' . $id);
            }
            // Poznamka je dobrovolna a jen u polozek, ktere ji umoznuji.
            $note = ((int) $leaf['has_note'] === 1) ? (trim((string) input('death_cause_note')) ?: null) : null;

            (new DogRepository())->setAliveStatus((int) $id, (int) $owner['id'], false, $deathIso, $note, 'owner', $causeId, (string) $leaf['label']);
            AuditService::log(Auth::id(), 'owner', 'dog_death_reported', 'dog', $id, null, ['death_date' => $deathIso, 'cause_id' => $causeId]);
            Session::flash('portal_notice', 'Děkujeme, zaznamenali jsme informace o úmrtí.');
        } else {
            (new DogRepository())->setAliveStatus((int) $id, (int) $owner['id'], true, null, null);
            AuditService::log(Auth::id(), 'owner', 'dog_alive_confirmed', 'dog', $id);
            Session::flash('portal_notice', 'Děkujeme za potvrzení, že pes žije.');
        }
        redirect('/portal/dogs/' . $id);
    }

    public function uploadDocument(string $id): string
    {
        Csrf::verify();
        [$owner, $dog] = $this->ownerAndDog((int) $id, true);
        if ($dog === null) {
            Session::flash('portal_error', 'K tomuto psovi nemáte přístup.');
            redirect('/portal');
        }

        try {
            $stored = FileStorage::store($_FILES['document'] ?? [], (string) $dog['breed_slug'], (int) $owner['id'], (int) $id, 'health');
            $fileId = (new FilesRepository())->create('dog', (int) $id, $stored['original'], $stored['relative'], $stored['mime'], $stored['size'], Auth::id());
            (new DogRepository())->addHealthDocument((int) $id, (int) $owner['id'], $fileId, trim((string) input('document_type')) ?: null, Dates::fromCz((string) input('document_date')), trim((string) input('note')) ?: null);
            AuditService::log(Auth::id(), 'owner', 'health_document_uploaded', 'dog', $id, null, ['file_id' => $fileId]);
            Session::flash('portal_notice', 'Dokument byl nahrán.');
        } catch (\Throwable $e) {
            Session::flash('portal_error', 'Nahrání se nepodařilo: ' . $e->getMessage());
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
            'title' => 'Moje kontaktní údaje',
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
        Session::flash('portal_notice', 'Kontaktní údaje byly uloženy.');
        redirect('/portal/contacts');
    }

    /**
     * Fallback onboarding v portalu (pro majitele, kteri heslo nastavili drive,
     * nez onboarding vznikl). Nova registrace bezi uz na strance z pozvanky.
     */
    public function onboarding(): string
    {
        $owner = (new OwnerRepository())->findByUserId((int) Auth::id());
        if ($owner === null || !empty($owner['onboarding_completed_at'])) {
            redirect('/portal');
        }

        return view('portal/onboarding', array_merge(
            (new OwnerOnboardingService())->viewData((int) $owner['id']),
            ['title' => 'Kontrola údajů', 'error' => Session::flash('portal_error')]
        ));
    }

    public function onboardingSubmit(): string
    {
        Csrf::verify();
        $owner = (new OwnerRepository())->findByUserId((int) Auth::id());
        if ($owner === null) {
            redirect('/portal');
        }
        if (empty(input('main_consent'))) {
            Session::flash('portal_error', 'Bez souhlasu se zpracováním údajů nelze pokračovat.');
            redirect('/portal/onboarding');
        }

        (new OwnerOnboardingService())->applyFromRequest((int) $owner['id'], Auth::id());
        Session::flash('portal_notice', 'Děkujeme, vaše údaje byly uloženy.');
        redirect('/portal');
    }

    public function settings(): string
    {
        $owner = (new OwnerRepository())->findByUserId((int) Auth::id());

        return view('portal/settings', [
            'title' => 'Nastavení',
            'owner' => $owner,
            'notice' => Session::flash('portal_notice'),
            'error' => Session::flash('portal_error'),
        ]);
    }

    public function changePassword(): string
    {
        Csrf::verify();
        $user = Auth::user();
        if ($user === null) {
            redirect('/portal');
        }

        $current = (string) input('current_password');
        $new = (string) input('new_password');
        $confirm = (string) input('new_password_confirm');

        if (empty($user['password_hash']) || !password_verify($current, (string) $user['password_hash'])) {
            Session::flash('portal_error', 'Současné heslo není správné.');
            redirect('/portal/settings');
        }
        if (strlen($new) < 10) {
            Session::flash('portal_error', 'Nové heslo musí mít alespoň 10 znaků.');
            redirect('/portal/settings');
        }
        if ($new !== $confirm) {
            Session::flash('portal_error', 'Nová hesla se neshodují.');
            redirect('/portal/settings');
        }

        (new UserRepository())->updatePasswordHash((int) $user['id'], Auth::hash($new));
        AuditService::log((int) $user['id'], 'owner', 'password_changed', 'user', (string) $user['id']);
        Session::flash('portal_notice', 'Heslo bylo změněno.');
        redirect('/portal/settings');
    }

    public function updateConsent(): string
    {
        Csrf::verify();
        $repo = new OwnerRepository();
        $owner = $repo->findByUserId((int) Auth::id());
        if ($owner === null) {
            redirect('/portal');
        }

        $consent = !empty(input('contact_consent'));
        $repo->setContactConsent((int) $owner['id'], $consent);
        AuditService::log(Auth::id(), 'owner', 'owner_consent_updated', 'owner', (string) $owner['id'], null, ['contact_consent' => $consent]);
        Session::flash('portal_notice', $consent ? 'Souhlas byl uložen.' : 'Souhlas byl odvolán.');
        redirect('/portal/settings');
    }

    /** Prehled vyplnenych dotazniku majitele (jen k nahlednuti). */
    public function forms(): string
    {
        $ownerRepo = new OwnerRepository();
        $owner = $ownerRepo->findByUserId((int) Auth::id());

        return view('portal/forms', [
            'title' => 'Dotazníky',
            'responses' => $owner !== null ? (new FormResponseRepository())->responsesForOwner((int) $owner['id']) : [],
            'notice' => Session::flash('portal_notice'),
        ]);
    }

    /** Detail vyplneneho dotazniku (read-only). */
    public function formResponse(string $id): string
    {
        $ownerRepo = new OwnerRepository();
        $owner = $ownerRepo->findByUserId((int) Auth::id());
        $responses = new FormResponseRepository();
        $response = $responses->find((int) $id);

        if ($owner === null || $response === null || !$ownerRepo->ownsDog((int) $owner['id'], (int) $response['dog_id'], true)) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Dotazník nenalezen']);
        }

        return view('portal/form_response', [
            'title' => 'Dotazník',
            'response' => $response,
            'answers' => $responses->answers((int) $id),
        ]);
    }

    /** Prehled zprav: obecne vlakno + vlakna podle psu (bez nacteni cele konverzace). */
    public function messages(): string
    {
        $ownerRepo = new OwnerRepository();
        $owner = $ownerRepo->findByUserId((int) Auth::id());
        $messages = new MessageRepository();

        $uid = (int) Auth::id();
        $general = null;
        $dogs = [];
        if ($owner !== null) {
            $genThread = $messages->ownerThread((int) $owner['id']);
            $general = [
                'thread' => $genThread,
                'count' => $genThread !== null ? $messages->countMessages((int) $genThread['id']) : 0,
                'unseen' => $genThread !== null && $messages->hasUnseenForUser((int) $genThread['id'], $uid),
            ];
            foreach ($ownerRepo->dogsOf((int) $owner['id']) as $d) {
                if ((int) $d['is_current'] !== 1) {
                    continue;
                }
                $thread = $messages->dogThread((int) $d['id']);
                $dogs[] = [
                    'dog' => $d,
                    'thread' => $thread,
                    'count' => $thread !== null ? $messages->countMessages((int) $thread['id']) : 0,
                    'unseen' => $thread !== null && $messages->hasUnseenForUser((int) $thread['id'], $uid),
                ];
            }
        }

        return view('portal/messages', [
            'title' => 'Zprávy',
            'owner' => $owner,
            'general' => $general,
            'dogs' => $dogs,
            'notice' => Session::flash('portal_notice'),
            'error' => Session::flash('portal_error'),
        ]);
    }

    /** Detail konverzace: ref = 'general' (obecne) nebo ID psa. */
    public function messagesThread(string $ref): string
    {
        $ownerRepo = new OwnerRepository();
        $owner = $ownerRepo->findByUserId((int) Auth::id());
        if ($owner === null) {
            redirect('/portal');
        }
        $messages = new MessageRepository();

        if ($ref === 'general') {
            $thread = $messages->ownerThread((int) $owner['id']);
            $heading = 'Obecná zpráva';
            $dogId = 0;
        } else {
            $dogId = (int) $ref;
            if ($dogId <= 0 || !$ownerRepo->ownsDog((int) $owner['id'], $dogId, true)) {
                http_response_code(404);
                return view('errors/404', ['title' => 'Konverzace nenalezena']);
            }
            $dog = (new DogRepository())->find($dogId);
            $thread = $messages->dogThread($dogId);
            $heading = ($dog['name'] ?? 'Pes') . ' / ' . ($dog['breed_name'] ?? '');
        }

        // Otevrenim vlakna se zpravy oznaci jako zobrazene.
        if ($thread !== null) {
            $messages->markRead((int) $thread['id'], (int) Auth::id());
        }

        return view('portal/messages_thread', [
            'title' => 'Zprávy',
            'heading' => $heading,
            'dogId' => $dogId,
            'messages' => $thread !== null ? $messages->messages((int) $thread['id']) : [],
            'notice' => Session::flash('portal_notice'),
            'error' => Session::flash('portal_error'),
        ]);
    }

    /** Odeslani zpravy: bez dog_id = obecne vlakno, jinak vlakno psa. */
    public function postMessage(): string
    {
        Csrf::verify();
        $ownerRepo = new OwnerRepository();
        $owner = $ownerRepo->findByUserId((int) Auth::id());
        if ($owner === null) {
            redirect('/portal');
        }
        $ownerId = (int) $owner['id'];
        $dogId = (int) input('dog_id');
        $target = $dogId > 0 ? '/portal/messages/' . $dogId : '/portal/messages/general';

        $body = trim((string) input('body'));
        if ($body === '') {
            Session::flash('portal_error', 'Zpráva nesmí být prázdná.');
            redirect($target);
        }

        $messages = new MessageRepository();
        if ($dogId > 0 && $ownerRepo->ownsDog($ownerId, $dogId, true)) {
            $threadId = $messages->findOrCreateDogThread($dogId, Auth::id());
            AuditService::log(Auth::id(), 'owner', 'message_sent', 'dog', (string) $dogId);
        } else {
            $threadId = $messages->findOrCreateOwnerThread($ownerId, Auth::id());
            AuditService::log(Auth::id(), 'owner', 'message_sent', 'owner', (string) $ownerId);
            $target = '/portal/messages/general';
        }
        $messages->addMessage($threadId, Auth::id(), 'owner', $body, 'open');
        Session::flash('portal_notice', 'Zpráva byla odeslána výzkumnému týmu.');
        redirect($target);
    }

    /**
     * @param array<string, mixed> $q
     * @param array<string, mixed> $config
     * @param array<string, mixed> $answersByKey
     */
    private function maybeHealthEvent(array $q, array $config, array $answersByKey, int $dogId, ?int $breedId, int $responseId): void
    {
        $type = $config['health_event']['type'] ?? null;
        if ($type === null) {
            return;
        }
        $val = $answersByKey[$q['question_key']] ?? '';
        $hasAnswer = is_array($val) ? $val !== [] : trim((string) $val) !== '';
        if (!$hasAnswer) {
            return;
        }
        $eventDate = ($q['type'] === 'date' && \App\Services\DogOwnerImporter::isDate((string) $val)) ? (string) $val : null;
        $code = is_array($val) ? implode(',', array_map('strval', $val)) : (string) $val;
        (new HealthEventRepository())->create(
            $dogId,
            $breedId,
            (string) $type,
            $eventDate,
            'owner_form',
            $responseId,
            substr($code, 0, 120),
            ['answer' => $val],
            (string) $q['label'],
            Auth::id()
        );
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
