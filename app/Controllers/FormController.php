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
use App\Repositories\TranslationRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\BreedContext;
use App\Services\FormBroadcastService;
use App\Support\FormSchema;
use App\Support\I18n;

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
            Session::flash('form_error', t('Nejdříve nahoře vyberte konkrétní plemeno.'));
            redirect('/admin/forms');
        }
        if ($name === '') {
            Session::flash('form_error', t('Zadejte název dotazníku.'));
            redirect('/admin/forms');
        }

        $id = (new FormRepository())->createDefinition($breedId, $name, trim((string) input('description')) ?: null, Auth::id());
        AuditService::log(Auth::id(), Auth::role(), 'form_created', 'form_definition', (string) $id, null, ['name' => $name]);
        Session::flash('form_notice', t('Dotazník vytvořen. Přidejte otázky a publikujte.'));
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
            Session::flash('form_error', t('Dotazník nejdříve publikujte, teprve pak jej lze rozeslat.'));
            redirect('/admin/forms/' . $id);
        }

        $dogsRepo = new DogRepository();
        $breedId = (int) $def['breed_id'];
        $hasEmail = static fn (array $r): bool => trim((string) ($r['email'] ?? '')) !== '';

        $living = $dogsRepo->recipientsForBreed($breedId, true);
        $all = $dogsRepo->recipientsForBreed($breedId, false);

        return view('admin/forms/broadcast', [
            'title' => 'Rozeslat dotazník',
            'def' => $def,
            'livingCount' => count($living),
            'livingEmailCount' => count(array_filter($living, $hasEmail)),
            'allCount' => count($all),
            'allEmailCount' => count(array_filter($all, $hasEmail)),
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
            Session::flash('form_error', t('Dotazník nejdříve publikujte, teprve pak jej lze rozeslat.'));
            redirect('/admin/forms/' . $id);
        }

        $subject = trim((string) input('subject'));
        $body = trim((string) input('body'));
        if ($subject === '' || $body === '') {
            Session::flash('form_error', t('Vyplňte předmět i text e-mailu.'));
            redirect('/admin/forms/' . $id . '/send');
        }

        $livingOnly = (string) input('recipients') !== 'all';
        $result = (new FormBroadcastService())->send($def, $published, $subject, $body, Auth::id(), $livingOnly);

        if ($result['total'] === 0) {
            Session::flash('form_error', $livingOnly
                ? t('Pro toto plemeno nejsou žádní majitelé žijících psů.')
                : t('Pro toto plemeno nejsou žádní majitelé psů.'));
        } else {
            $msg = t('Rozesláno: {sent} e-mailů', ['sent' => $result['sent']]);
            if ($result['skipped'] > 0) {
                $msg .= t(', přeskočeno bez e-mailu: {skipped}', ['skipped' => $result['skipped']]);
            }
            if ($result['failed'] > 0) {
                $msg .= t(', nedoručeno: {failed} (viz e-mail log)', ['failed' => $result['failed']]);
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
            Session::flash('form_error', t('Vyberte typ a zadejte text otázky.'));
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
        Session::flash('form_notice', t('Otázka přidána.'));
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
            Session::flash('form_error', t('Publikovanou verzi nelze upravovat. Vytvořte novou verzi.'));
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
            Session::flash('form_error', t('Otázku nelze upravit.'));
            redirect('/admin/forms/' . $id);
        }

        $type = (string) input('type');
        $label = trim((string) input('label'));
        if (!FormSchema::isValidType($type) || $label === '') {
            Session::flash('form_error', t('Vyberte typ a zadejte text otázky.'));
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

        Session::flash('form_notice', t('Otázka uložena.'));
        redirect('/admin/forms/' . $id);
    }

    public function deleteQuestion(string $id, string $qid): string
    {
        Csrf::verify();
        $repo = new FormRepository();
        $q = $repo->findQuestion((int) $qid);
        if ($q !== null && (int) $q['form_definition_id'] === (int) $id && $q['version_status'] === 'draft') {
            $repo->deleteQuestion((int) $qid);
            Session::flash('form_notice', t('Otázka smazána.'));
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
            Session::flash('form_notice', t('Dotazník byl publikován.'));
        } catch (\Throwable $e) {
            Session::flash('form_error', $e->getMessage());
        }
        redirect('/admin/forms/' . $id);
    }

    public function newVersion(string $id): string
    {
        Csrf::verify();
        (new FormRepository())->ensureDraft((int) $id);
        Session::flash('form_notice', t('Vytvořena nová (draft) verze - můžete upravovat.'));
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
            'answers' => $repo->answersLocalized((int) $id, \App\Support\I18n::locale()),
        ]);
    }

    /** Obrazovka prekladu dotazniku (jazyk se vybira nahore, tabulka zdroj -> preklad). */
    public function translations(string $id): string
    {
        $repo = new FormRepository();
        $def = $repo->findDefinition((int) $id);
        if ($def === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Dotazník nenalezen']);
        }

        $editing = $repo->draftVersion((int) $id) ?? $repo->publishedVersion((int) $id);
        $questions = $editing !== null ? $repo->questions((int) $editing['id']) : [];
        $options = $editing !== null ? $repo->optionsByQuestion((int) $editing['id']) : [];

        $target = $this->targetLocales();
        $lang = (string) input('lang');
        if (!in_array($lang, $target, true)) {
            $lang = $target[0] ?? 'en';
        }

        $tx = new TranslationRepository();
        $qIds = array_map(static fn ($q): int => (int) $q['id'], $questions);
        $optIds = [];
        foreach ($options as $list) {
            foreach ($list as $o) {
                $optIds[] = (int) $o['id'];
            }
        }

        return view('admin/forms/translations', [
            'title' => 'Překlady dotazníku',
            'def' => $def,
            'editing' => $editing,
            'questions' => $questions,
            'options' => $options,
            'targetLocales' => $target,
            'lang' => $lang,
            'defTx' => $tx->allForEntities(TranslationRepository::FORM_DEFINITION, [(int) $def['id']], $lang)[(int) $def['id']] ?? [],
            'qTx' => $tx->allForEntities(TranslationRepository::FORM_QUESTION, $qIds, $lang),
            'oTx' => $tx->allForEntities(TranslationRepository::FORM_OPTION, $optIds, $lang),
            'notice' => Session::flash('form_notice'),
            'error' => Session::flash('form_error'),
        ]);
    }

    /** Ulozeni prekladu pro jeden jazyk (prazdne pole = smazani -> fallback na cestinu). */
    public function saveTranslations(string $id): string
    {
        Csrf::verify();
        $repo = new FormRepository();
        $def = $repo->findDefinition((int) $id);
        if ($def === null) {
            redirect('/admin/forms');
        }

        $lang = (string) input('lang');
        if (!in_array($lang, $this->targetLocales(), true)) {
            Session::flash('form_error', t('Neplatný jazyk.'));
            redirect('/admin/forms/' . $id . '/translations');
        }

        $editing = $repo->draftVersion((int) $id) ?? $repo->publishedVersion((int) $id);
        $questions = $editing !== null ? $repo->questions((int) $editing['id']) : [];
        $options = $editing !== null ? $repo->optionsByQuestion((int) $editing['id']) : [];

        $tx = new TranslationRepository();
        // Definice (popis jen kdyz ma cesky zdroj - jinak by vznikl orphan).
        $tx->set(TranslationRepository::FORM_DEFINITION, (int) $def['id'], 'name', $lang, (string) input('def_name'));
        if (trim((string) ($def['description'] ?? '')) !== '') {
            $tx->set(TranslationRepository::FORM_DEFINITION, (int) $def['id'], 'description', $lang, (string) input('def_description'));
        }

        // Otazky (jen id z editovane verze; help jen kdyz ma cesky zdroj).
        $qLabel = (array) ($_POST['q_label'] ?? []);
        $qHelp = (array) ($_POST['q_help'] ?? []);
        foreach ($questions as $q) {
            $qid = (int) $q['id'];
            $tx->set(TranslationRepository::FORM_QUESTION, $qid, 'label', $lang, (string) ($qLabel[$qid] ?? ''));
            if (trim((string) ($q['help_text'] ?? '')) !== '') {
                $tx->set(TranslationRepository::FORM_QUESTION, $qid, 'help_text', $lang, (string) ($qHelp[$qid] ?? ''));
            }
        }

        // Moznosti.
        $oLabel = (array) ($_POST['o_label'] ?? []);
        foreach ($options as $list) {
            foreach ($list as $o) {
                $oid = (int) $o['id'];
                $tx->set(TranslationRepository::FORM_OPTION, $oid, 'label', $lang, (string) ($oLabel[$oid] ?? ''));
            }
        }

        AuditService::log(Auth::id(), Auth::role(), 'form_translations_saved', 'form_definition', $id, null, ['locale' => $lang]);
        Session::flash('form_notice', t('Překlady uloženy ({lang}).', ['lang' => I18n::name($lang)]));
        redirect('/admin/forms/' . $id . '/translations?lang=' . $lang);
    }

    /** @return array<int, string> cilove jazyky (vsechny krome zdrojoveho cs) */
    private function targetLocales(): array
    {
        return array_values(array_filter(
            array_keys(I18n::available()),
            static fn (string $l): bool => $l !== I18n::defaultLocale()
        ));
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
