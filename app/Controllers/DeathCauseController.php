<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\BreedRepository;
use App\Repositories\DeathCauseRepository;
use App\Repositories\TranslationRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Support\I18n;

/**
 * Editor ciselniku pricin umrti (Nastaveni -> Priciny umrti) per plemeno.
 * Strom (kategorie -> nemoci) se pridava/upravuje/maze/prehazuje jako u dotazniku.
 * Kod (code) je nemenny stabilni klic; preklady labelu jdou do DB translations.
 */
final class DeathCauseController
{
    public function index(): string
    {
        [$breeds, $breedId] = $this->pickBreed();

        return view('admin/death_causes/index', [
            'title' => 'Příčiny úmrtí',
            'breeds' => $breeds,
            'breedId' => $breedId,
            'nodes' => $breedId > 0 ? (new DeathCauseRepository())->editorTree($breedId) : [],
            'notice' => Session::flash('cause_notice'),
            'error' => Session::flash('cause_error'),
        ]);
    }

    public function store(): string
    {
        Csrf::verify();
        $repo = new DeathCauseRepository();

        $breedId = (int) input('breed');
        if ((new BreedRepository())->find($breedId) === null) {
            Session::flash('cause_error', t('Vyberte plemeno.'));
            redirect('/admin/death-causes');
        }

        $label = trim((string) input('label'));
        if ($label === '') {
            Session::flash('cause_error', t('Zadejte název příčiny.'));
            redirect('/admin/death-causes?breed=' . $breedId);
        }

        // Rodic (nepovinny) musi patrit stejnemu plemeni.
        $parentId = null;
        $parentRaw = (int) input('parent_id');
        if ($parentRaw > 0) {
            $parent = $repo->findById($parentRaw);
            if ($parent === null || (int) $parent['breed_id'] !== $breedId) {
                Session::flash('cause_error', t('Neplatná nadřízená kategorie.'));
                redirect('/admin/death-causes?breed=' . $breedId);
            }
            $parentId = $parentRaw;
        }

        $newId = $repo->create($breedId, $parentId, $label, (bool) input('has_note'));
        AuditService::log(Auth::id(), Auth::role(), 'death_cause_created', 'death_cause', (string) $newId, null, ['breed_id' => $breedId, 'label' => $label]);
        Session::flash('cause_notice', t('Příčina přidána.'));
        redirect('/admin/death-causes?breed=' . $breedId);
    }

    public function edit(string $id): string
    {
        $node = (new DeathCauseRepository())->findById((int) $id);
        if ($node === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Příčina nenalezena']);
        }
        $breed = (new BreedRepository())->find((int) $node['breed_id']);

        return view('admin/death_causes/edit', [
            'title' => 'Upravit příčinu',
            'node' => $node,
            'breedName' => $breed !== null ? (string) $breed['name'] : '',
            'error' => Session::flash('cause_error'),
        ]);
    }

    public function update(string $id): string
    {
        Csrf::verify();
        $repo = new DeathCauseRepository();
        $node = $repo->findById((int) $id);
        if ($node === null) {
            redirect('/admin/death-causes');
        }

        $label = trim((string) input('label'));
        if ($label === '') {
            Session::flash('cause_error', t('Zadejte název příčiny.'));
            redirect('/admin/death-causes/' . (int) $id . '/edit');
        }

        $repo->update((int) $id, $label, (bool) input('has_note'));
        AuditService::log(Auth::id(), Auth::role(), 'death_cause_updated', 'death_cause', (string) (int) $id, null, ['label' => $label]);
        Session::flash('cause_notice', t('Příčina uložena.'));
        redirect('/admin/death-causes?breed=' . (int) $node['breed_id']);
    }

    public function destroy(string $id): string
    {
        Csrf::verify();
        $repo = new DeathCauseRepository();
        $node = $repo->findById((int) $id);
        if ($node === null) {
            redirect('/admin/death-causes');
        }
        $breedId = (int) $node['breed_id'];

        if ($repo->childrenCount((int) $id) > 0) {
            Session::flash('cause_error', t('Nelze smazat: příčina má podřízené položky. Nejdříve smažte je.'));
            redirect('/admin/death-causes?breed=' . $breedId);
        }
        if ($repo->dogUsageCount((int) $id) > 0) {
            Session::flash('cause_error', t('Nelze smazat: příčina je přiřazena u některých psů.'));
            redirect('/admin/death-causes?breed=' . $breedId);
        }

        $repo->delete((int) $id);
        AuditService::log(Auth::id(), Auth::role(), 'death_cause_deleted', 'death_cause', (string) (int) $id, ['label' => (string) $node['label']], null);
        Session::flash('cause_notice', t('Příčina smazána.'));
        redirect('/admin/death-causes?breed=' . $breedId);
    }

    public function move(string $id): string
    {
        Csrf::verify();
        $repo = new DeathCauseRepository();
        $node = $repo->findById((int) $id);
        if ($node !== null) {
            $repo->move((int) $id, (string) input('dir') === 'up' ? 'up' : 'down');
        }
        redirect('/admin/death-causes?breed=' . ($node !== null ? (int) $node['breed_id'] : ''));
    }

    // ----- Preklady labelu (jeden jazyk najednou, prazdne = fallback na cestinu) -----

    public function translations(): string
    {
        [$breeds, $breedId] = $this->pickBreed();
        $nodes = $breedId > 0 ? (new DeathCauseRepository())->editorTree($breedId) : [];

        $target = $this->targetLocales();
        $lang = (string) input('lang');
        if (!in_array($lang, $target, true)) {
            $lang = $target[0] ?? 'en';
        }

        $ids = array_map(static fn (array $n): int => (int) $n['id'], $nodes);
        $tx = (new TranslationRepository())->allForEntities(TranslationRepository::DEATH_CAUSE, $ids, $lang);

        return view('admin/death_causes/translations', [
            'title' => 'Překlady příčin úmrtí',
            'breeds' => $breeds,
            'breedId' => $breedId,
            'nodes' => $nodes,
            'targetLocales' => $target,
            'lang' => $lang,
            'tx' => $tx,
            'notice' => Session::flash('cause_notice'),
            'error' => Session::flash('cause_error'),
        ]);
    }

    public function saveTranslations(): string
    {
        Csrf::verify();
        $breedId = (int) input('breed');
        $lang = (string) input('lang');
        if (!in_array($lang, $this->targetLocales(), true)) {
            Session::flash('cause_error', t('Neplatný jazyk.'));
            redirect('/admin/death-causes/translations?breed=' . $breedId);
        }

        $nodes = (new DeathCauseRepository())->editorTree($breedId);
        $tx = new TranslationRepository();
        $labels = (array) ($_POST['label'] ?? []);
        foreach ($nodes as $n) {
            $nid = (int) $n['id'];
            $tx->set(TranslationRepository::DEATH_CAUSE, $nid, 'label', $lang, (string) ($labels[$nid] ?? ''));
        }

        AuditService::log(Auth::id(), Auth::role(), 'death_cause_translations_saved', 'breed', (string) $breedId, null, ['lang' => $lang]);
        Session::flash('cause_notice', t('Překlady uloženy.'));
        redirect('/admin/death-causes/translations?breed=' . $breedId . '&lang=' . rawurlencode($lang));
    }

    /**
     * Vyber plemene ze seznamu (vsechna) - z ?breed=, jinak prvni.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: int}
     */
    private function pickBreed(): array
    {
        $breeds = (new BreedRepository())->all(false);
        $ids = array_map(static fn (array $b): int => (int) $b['id'], $breeds);
        $breedId = (int) input('breed');
        if (!in_array($breedId, $ids, true)) {
            $breedId = $ids[0] ?? 0;
        }
        return [$breeds, $breedId];
    }

    /** @return array<int, string> cilove jazyky (vse krome zdrojoveho cs) */
    private function targetLocales(): array
    {
        return array_values(array_filter(
            array_keys(I18n::available()),
            static fn (string $l): bool => $l !== I18n::defaultLocale()
        ));
    }
}
