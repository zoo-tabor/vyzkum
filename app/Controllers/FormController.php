<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\BreedRepository;
use App\Repositories\FormRepository;
use App\Repositories\FormResponseRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\BreedContext;
use App\Support\FormSchema;

final class FormController
{
    public function index(): string
    {
        $repo = new FormRepository();
        $breedId = BreedContext::current();

        return view('admin/forms/index', [
            'title' => 'Formulare',
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
        $breedId = (int) input('breed_id');
        $name = trim((string) input('name'));
        if ($breedId <= 0 || $name === '') {
            Session::flash('form_error', 'Vyberte plemeno a zadejte nazev dotazniku.');
            redirect('/admin/forms');
        }

        $id = (new FormRepository())->createDefinition($breedId, $name, trim((string) input('description')) ?: null, Auth::id());
        AuditService::log(Auth::id(), Auth::role(), 'form_created', 'form_definition', (string) $id, null, ['name' => $name]);
        Session::flash('form_notice', 'Dotaznik vytvoren. Pridejte otazky a publikujte.');
        redirect('/admin/forms/' . $id);
    }

    public function show(string $id): string
    {
        $repo = new FormRepository();
        $def = $repo->findDefinition((int) $id);
        if ($def === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Dotaznik nenalezen']);
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
            'notice' => Session::flash('form_notice'),
            'error' => Session::flash('form_error'),
        ]);
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
            Session::flash('form_error', 'Vyberte typ a zadejte text otazky.');
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
        Session::flash('form_notice', 'Otazka pridana.');
        redirect('/admin/forms/' . $id);
    }

    public function editQuestion(string $id, string $qid): string
    {
        $repo = new FormRepository();
        $q = $repo->findQuestion((int) $qid);
        if ($q === null || (int) $q['form_definition_id'] !== (int) $id) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Otazka nenalezena']);
        }
        if ($q['version_status'] !== 'draft') {
            Session::flash('form_error', 'Publikovanou verzi nelze upravovat. Vytvorte novou verzi.');
            redirect('/admin/forms/' . $id);
        }

        return view('admin/forms/question', [
            'title' => 'Upravit otazku',
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
            Session::flash('form_error', 'Otazku nelze upravit.');
            redirect('/admin/forms/' . $id);
        }

        $type = (string) input('type');
        $label = trim((string) input('label'));
        if (!FormSchema::isValidType($type) || $label === '') {
            Session::flash('form_error', 'Vyberte typ a zadejte text otazky.');
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

        Session::flash('form_notice', 'Otazka ulozena.');
        redirect('/admin/forms/' . $id);
    }

    public function deleteQuestion(string $id, string $qid): string
    {
        Csrf::verify();
        $repo = new FormRepository();
        $q = $repo->findQuestion((int) $qid);
        if ($q !== null && (int) $q['form_definition_id'] === (int) $id && $q['version_status'] === 'draft') {
            $repo->deleteQuestion((int) $qid);
            Session::flash('form_notice', 'Otazka smazana.');
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
            Session::flash('form_notice', 'Dotaznik byl publikovan.');
        } catch (\Throwable $e) {
            Session::flash('form_error', $e->getMessage());
        }
        redirect('/admin/forms/' . $id);
    }

    public function newVersion(string $id): string
    {
        Csrf::verify();
        (new FormRepository())->ensureDraft((int) $id);
        Session::flash('form_notice', 'Vytvorena nova (draft) verze - muzete upravovat.');
        redirect('/admin/forms/' . $id);
    }

    public function response(string $id): string
    {
        $repo = new FormResponseRepository();
        $response = $repo->find((int) $id);
        if ($response === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Odpoved nenalezena']);
        }

        return view('admin/forms/response', [
            'title' => 'Odpoved dotazniku',
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
        $q = trim((string) input('visible_if_question'));
        $v = trim((string) input('visible_if_value'));
        if ($q === '' || $v === '') {
            return null;
        }
        return json_encode(['visible_if' => ['q' => $q, 'eq' => $v]], JSON_UNESCAPED_UNICODE);
    }
}
