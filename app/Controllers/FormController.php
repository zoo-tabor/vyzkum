<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\BreedRepository;
use App\Repositories\DogRepository;
use App\Repositories\FormAssignmentRepository;
use App\Repositories\FormRepository;
use App\Repositories\FormResponseRepository;
use App\Repositories\HealthEventRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\BreedContext;
use App\Services\FormBroadcastService;
use App\Support\FormSchema;

final class FormController
{
    public function index(): string
    {
        $repo = new FormRepository();
        $breedId = BreedContext::current();

        return view('admin/forms/index', [
            'title' => 'Formuláře',
            'forms' => $repo->listByBreed($breedId),
            'breeds' => (new BreedRepository())->all(false),
            'currentBreedId' => $breedId,
            'notice' => Session::flash('form_notice'),
            'error' => Session::flash('form_error'),
        ]);
    }

    public function create(): string
    {
        Csrf::verify();
        // Plemeno se bere z prepinace nahore (kontext), samostatny vyber je zbytecny.
        $breedId = (int) BreedContext::current();
        $name = trim((string) input('name'));
        if ($breedId <= 0) {
            Session::flash('form_error', 'Nejdříve nahoře vyberte konkrétní plemeno.');
            redirect('/admin/forms');
        }
        if ($name === '') {
            Session::flash('form_error', 'Zadejte název dotazníku.');
            redirect('/admin/forms');
        }

        $id = (new FormRepository())->createDefinition($breedId, $name, trim((string) input('description')) ?: null, Auth::id());
        AuditService::log(Auth::id(), Auth::role(), 'form_created', 'form_definition', (string) $id, null, ['name' => $name]);
        Session::flash('form_notice', 'Dotazník vytvořen. Přidejte otázky a publikujte.');
        redirect('/admin/forms/' . $id);
    }

    public function show(string $id): string
    {
        $repo = new FormRepository();
        $def = $repo->findDefinition((int) $id);
        if ($def === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Dotazník nenalezen']);
        }

        $draft = $repo->draftVersion((int) $id);
        $published = $repo->publishedVersion((int) $id);
        $editing = $draft ?? $published;
        $questions = $editing !== null ? $repo->questions((int) $editing['id']) : [];
        $options = $editing !== null ? $repo->optionsByQuestion((int) $editing['id']) : [];

        return view('admin/forms/show', [
            'title' => $def['name'],
            'def' => $def,
            'draft' => $draft,
            'published' => $published,
            'editing' => $editing,
            'canEdit' => $draft !== null && $editing !== null && (int) $editing['id'] === (int) $draft['id'],
            'questions' => $questions,
            'options' => $options,
            'assignmentStats' => (new FormAssignmentRepository())->statsForDefinition((int) $id),
            'notice' => Session::flash('form_notice'),
            'error' => Session::flash('form_error'),
        ]);
    }

    public function broadcastForm(string $id): string
    {
        $repo = new FormRepository();
        $def = $repo->findDefinition((int) $id);
        if ($def === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Dotazník nenalezen']);
        }
        $published = $repo->publishedVersion((int) $id);
        if ($published === null) {
            Session::flash('form_error', 'Dotazník nejdříve publikujte, teprve pak jej lze rozeslat.');
            redirect('/admin/forms/' . $id);
        }

        $recipients = (new DogRepository())->recipientsForBreed((int) $def['breed_id']);
        $withEmail = array_filter($recipients, static fn ($r) => trim((string) ($r['email'] ?? '')) !== '');

        return view('admin/forms/broadcast', [
            'title' => 'Rozeslat dotazník',
            'def' => $def,
            'recipientCount' => count($recipients),
            'emailCount' => count($withEmail),
            'defaultSubject' => FormBroadcastService::DEFAULT_SUBJECT,
            'defaultBody' => FormBroadcastService::defaultBody((string) $def['name']),
            'error' => Session::flash('form_error'),
        ]);
    }

    public function sendBroadcast(string $id): string
    {
        Csrf::verify();
        $repo = new FormRepository();
        $def = $repo->findDefinition((int) $id);
        if ($def === null) {
            redirect('/admin/forms');
        }
        $published = $repo->publishedVersion((int) $id);
        if ($published === null) {
            Session::flash('form_error', 'Dotazník nejdříve publikujte, teprve pak jej lze rozeslat.');
            redirect('/admin/forms/' . $id);
        }

        $subject = trim((string) input('subject'));
        $body = trim((string) input('body'));
        if ($subject === '' || $body === '') {
            Session::flash('form_error', 'Vyplňte předmět i text e-mailu.');
            redirect('/admin/forms/' . $id . '/send');
        }

        $result = (new FormBroadcastService())->send($def, $published, $subject, $body, Auth::id());

        if ($result['total'] === 0) {
            Session::flash('form_error', 'Pro toto plemeno nejsou žádní majitelé žijících psů.');
        } else {
            $msg = 'Rozesláno: ' . $result['sent'] . ' e-mailů';
            if ($result['skipped'] > 0) {
                $msg .= ', přeskočeno bez e-mailu: ' . $result['skipped'];
            }
            if ($result['failed'] > 0) {
                $msg .= ', nedoručeno: ' . $result['failed'] . ' (viz e-mail log)';
            }
            Session::flash($result['failed'] > 0 ? 'form_error' : 'form_notice', $msg . '.');
        }
        redirect('/admin/forms/' . $id);
    }

    public function addQuestion(string $id): string
    {
        Csrf::verify();
        $repo = new FormRepository();
        $def = $repo->findDefinition((int) $id);
        if ($def === null) {
            redirect('/admin/forms');
        }

        $type = (string) input('type');
        $label = trim((string) input('label'));
        if (!FormSchema::isValidType($type) || $label === '') {
            Session::flash('form_error', 'Vyberte typ a zadejte text otázky.');
            redirect('/admin/forms/' . $id);
        }

        $version = $repo->ensureDraft((int) $id);
        $existing = array_map(static fn ($q): string => (string) $q['question_key'], $repo->questions((int) $version['id']));
        $key = $this->uniqueKey(FormSchema::slug($label), $existing);

        $qid = $repo->addQuestion((int) $version['id'], [
            'question_key' => $key,
            'label' => $label,
            'help_text' => trim((string) input('help_text')) ?: null,
            'type' => $type,
            'is_required' => (bool) input('is_required'),
            'config_json' => $this->buildConfig(),
        ]);

        if (FormSchema::needsOptions($type)) {
            $repo->replaceOptions($qid, FormSchema::parseOptions((string) input('options')));
        }

        AuditService::log(Auth::id(), Auth::role(), 'form_question_added', 'form_version', (string) $version['id'], null, ['key' => $key]);
        Session::flash('form_notice', 'Otázka přidána.');
        redirect('/admin/forms/' . $id);
    }

    public function editQuestion(string $id, string $qid): string
    {
        $repo = new FormRepository();
        $q = $repo->findQuestion((int) $qid);
        if ($q === null || (int) $q['form_definition_id'] !== (int) $id) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Otázka nenalezena']);
        }
        if ($q['version_status'] !== 'draft') {
            Session::flash('form_error', 'Publikovanou verzi nelze upravovat. Vytvořte novou verzi.');
            redirect('/admin/forms/' . $id);
        }

        return view('admin/forms/question', [
            'title' => 'Upravit otázku',
            'defId' => (int) $id,
            'question' => $q,
            'options' => $repo->optionsFor((int) $qid),
            'otherQuestions' => array_filter($repo->questions((int) $q['form_version_id']), static fn ($x) => (int) $x['id'] !== (int) $qid),
            'error' => Session::flash('form_error'),
        ]);
    }

    public function updateQuestion(string $id, string $qid): string
    {
        Csrf::verify();
        $repo = new FormRepository();
        $q = $repo->findQuestion((int) $qid);
        if ($q === null || (int) $q['form_definition_id'] !== (int) $id || $q['version_status'] !== 'draft') {
            Session::flash('form_error', 'Otázku nelze upravit.');
            redirect('/admin/forms/' . $id);
        }

        $type = (string) input('type');
        $label = trim((string) input('label'));
        if (!FormSchema::isValidType($type) || $label === '') {
            Session::flash('form_error', 'Vyberte typ a zadejte text otázky.');
            redirect('/admin/forms/' . $id . '/questions/' . $qid . '/edit');
        }

        $repo->updateQuestion((int) $qid, [
            'label' => $label,
            'help_text' => trim((string) input('help_text')) ?: null,
            'type' => $type,
            'is_required' => (bool) input('is_required'),
            'config_json' => $this->buildConfig(),
        ]);
        $repo->replaceOptions((int) $qid, FormSchema::needsOptions($type) ? FormSchema::parseOptions((string) input('options')) : []);

        Session::flash('form_notice', 'Otázka uložena.');
        redirect('/admin/forms/' . $id);
    }

    public function deleteQuestion(string $id, string $qid): string
    {
        Csrf::verify();
        $repo = new FormRepository();
        $q = $repo->findQuestion((int) $qid);
        if ($q !== null && (int) $q['form_definition_id'] === (int) $id && $q['version_status'] === 'draft') {
            $repo->deleteQuestion((int) $qid);
            Session::flash('form_notice', 'Otázka smazána.');
        }
        redirect('/admin/forms/' . $id);
    }

    public function moveQuestion(string $id, string $qid): string
    {
        Csrf::verify();
        $repo = new FormRepository();
        $q = $repo->findQuestion((int) $qid);
        if ($q !== null && (int) $q['form_definition_id'] === (int) $id && $q['version_status'] === 'draft') {
            $repo->move((int) $qid, (string) input('dir') === 'up' ? 'up' : 'down');
        }
        redirect('/admin/forms/' . $id);
    }

    public function publish(string $id): string
    {
        Csrf::verify();
        try {
            (new FormRepository())->publish((int) $id);
            AuditService::log(Auth::id(), Auth::role(), 'form_published', 'form_definition', $id);
            Session::flash('form_notice', 'Dotazník byl publikován.');
        } catch (\Throwable $e) {
            Session::flash('form_error', $e->getMessage());
        }
        redirect('/admin/forms/' . $id);
    }

    public function newVersion(string $id): string
    {
        Csrf::verify();
        (new FormRepository())->ensureDraft((int) $id);
        Session::flash('form_notice', 'Vytvořena nová (draft) verze - můžete upravovat.');
        redirect('/admin/forms/' . $id);
    }

    public function response(string $id): string
    {
        $repo = new FormResponseRepository();
        $response = $repo->find((int) $id);
        if ($response === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Odpověď nenalezena']);
        }

        return view('admin/forms/response', [
            'title' => 'Odpověď dotazníku',
            'response' => $response,
            'answers' => $repo->answers((int) $id),
        ]);
    }

    /** @param array<int, string> $existing */
    private function uniqueKey(string $base, array $existing): string
    {
        $key = $base;
        $i = 2;
        while (in_array($key, $existing, true)) {
            $key = $base . '_' . $i;
            $i++;
        }
        return substr($key, 0, 64);
    }

    private function buildConfig(): ?string
    {
        $config = [];

        $q = trim((string) input('visible_if_question'));
        $v = trim((string) input('visible_if_value'));
        if ($q !== '' && $v !== '') {
            $config['visible_if'] = ['q' => $q, 'eq' => $v];
        }

        $he = trim((string) input('health_event_type'));
        if ($he !== '' && in_array($he, HealthEventRepository::TYPES, true)) {
            $config['health_event'] = ['type' => $he];
        }

        return $config === [] ? null : json_encode($config, JSON_UNESCAPED_UNICODE);
    }
}
