<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\DogRepository;
use App\Repositories\GeneRepository;
use App\Repositories\GenotypeRepository;
use App\Repositories\SampleRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\BreedContext;
use App\Services\GeneticsImportService;
use App\Support\Csv;
use App\Support\Genetics;

final class GeneticsController
{
    private const TEMPLATE_HEADER = [
        'sample_id', 'expected_phenotype', 'B3GALNT1_genotype', 'NLRP1_genotype',
        'PARP14_genotype', 'COL9A1_genotype', 'lab_name', 'tested_at', 'notes',
    ];

    public function index(): string
    {
        $repo = new GenotypeRepository();
        $breedId = BreedContext::current();

        // Dashboard: jeden radek na psa, sloupec na kazdy gen sledovany u plemene.
        $genes = $repo->genesForBreed($breedId);
        $dogs = $repo->dogsWithGenotypes($breedId);
        $dogIds = array_map(static fn (array $d): int => (int) $d['id'], $dogs);

        return view('admin/genetics/index', [
            'title' => 'Genetika',
            'genes' => $genes,
            'dogs' => $dogs,
            'genotypes' => $repo->genotypesByDogGene($dogIds),
            'newestSample' => (new SampleRepository())->newestByDogIds($dogIds),
            'meta' => $repo->dashboardMetaByDog($breedId),
            'currentBreedId' => $breedId,
            'genePanel' => (new GeneRepository())->genesWithMarker(),
            'notice' => Session::flash('genetics_notice'),
            'error' => Session::flash('genetics_error'),
        ]);
    }

    /** Detail genetiky jednoho psa - jen genetika + editace. */
    public function show(string $id): string
    {
        $dog = (new DogRepository())->find((int) $id);
        if ($dog === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Pes nenalezen']);
        }

        $genotypes = new GenotypeRepository();

        return view('admin/genetics/dog', [
            'title' => 'Genetika: ' . $dog['name'],
            'dog' => $dog,
            'genePanel' => (new GeneRepository())->genesWithMarker(),
            'current' => $genotypes->genotypeMapForDog((int) $id),
            'currentNotes' => $genotypes->noteMapForDog((int) $id),
            'notice' => Session::flash('genetics_notice'),
            'error' => Session::flash('genetics_error'),
        ]);
    }

    /** Ulozeni editace genetiky psa: vyplnene geny ulozi/upravi, prazdne smazou. */
    public function update(string $id): string
    {
        Csrf::verify();
        $dog = (new DogRepository())->find((int) $id);
        if ($dog === null) {
            Session::flash('genetics_error', t('Pes nenalezen.'));
            redirect('/admin/genetics');
        }
        $dogId = (int) $id;
        $breedId = $dog['breed_id'] !== null ? (int) $dog['breed_id'] : null;

        $genotypes = new GenotypeRepository();

        // Volitelne datum testu (plati pro ukladane genotypy). Laborator se uz nevede.
        $testedAt = trim((string) input('tested_at'));
        $testId = $testedAt !== '' ? $genotypes->createTest($dogId, null, $testedAt, 'manual', null, null) : null;

        $values = (array) ($_POST['g'] ?? []);
        $notes = (array) ($_POST['n'] ?? []);
        $current = $genotypes->genotypeMapForDog($dogId);
        $saved = 0;
        $deleted = 0;
        $invalid = [];
        foreach ($values as $markerId => $raw) {
            $markerId = (int) $markerId;
            if ($markerId <= 0) {
                continue;
            }
            $value = trim((string) $raw);
            if ($value === '') {
                if (isset($current[$markerId])) {
                    $genotypes->deleteGenotype($dogId, $markerId);
                    $deleted++;
                }
                continue;
            }
            $split = Genetics::splitGenotype($value);
            if ($split === null) {
                $invalid[] = $value;
                continue;
            }
            // Zdroj se pri editaci nemeni (source = null -> zachova stavajici).
            $genotypes->upsertGenotype($dogId, $breedId, $markerId, $split['allele_1'], $split['allele_2'], $split['genotype'], $testId, 'manual');
            $genotypes->setGenotypeNote($dogId, $markerId, trim((string) ($notes[$markerId] ?? '')) ?: null);
            $saved++;
        }

        if ($saved > 0) {
            (new \App\Repositories\SampleRepository())->markAnalysisDoneForDog($dogId);
        }
        AuditService::log(Auth::id(), Auth::role(), 'genotype_edit', 'dog', (string) $dogId, null, ['saved' => $saved, 'deleted' => $deleted]);

        if ($invalid !== []) {
            Session::flash('genetics_error', t('Uloženo: {saved}, smazáno: {deleted}. Neplatný formát: {invalid}.', ['saved' => $saved, 'deleted' => $deleted, 'invalid' => implode(', ', $invalid)]));
        } else {
            Session::flash('genetics_notice', t('Uloženo: {saved} genotypů, smazáno: {deleted}.', ['saved' => $saved, 'deleted' => $deleted]));
        }
        redirect('/admin/genetics/' . $dogId);
    }

    /** Naseptavac psa podle jmena (JSON) pro rucni zadani genotypu. */
    public function dogSuggest(): never
    {
        $q = trim((string) input('q'));
        $items = $q === '' ? [] : (new DogRepository())->searchByName($q, BreedContext::current());

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($items, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Export dle dashboardu: jeden radek na psa, sloupec na kazdy gen plemene.
     * UTF-8 s BOM, oddelovac ";". Format: dog;breed;gen1;...;tested_at;status;source.
     */
    public function export(): never
    {
        $repo = new GenotypeRepository();
        $breedId = BreedContext::current();

        $genes = $repo->genesForBreed($breedId);
        $dogs = $repo->dogsWithGenotypes($breedId);
        $dogIds = array_map(static fn (array $d): int => (int) $d['id'], $dogs);
        $genos = $repo->genotypesByDogGene($dogIds);
        $meta = $repo->dashboardMetaByDog($breedId);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="genotypy_export_' . date('Ymd_His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');

        $header = ['dog', 'breed'];
        foreach ($genes as $g) {
            $header[] = $g['symbol'];
        }
        $header[] = 'tested_at';
        $header[] = 'status';
        $header[] = 'source';
        fputcsv($out, $header, ';');

        foreach ($dogs as $d) {
            $dogId = (int) $d['id'];
            $row = [$d['name'], $d['breed_name'] ?? ''];
            foreach ($genes as $g) {
                $row[] = $genos[$dogId][(int) $g['id']] ?? '';
            }
            $m = $meta[$dogId] ?? [];
            $row[] = $m['tested_at'] ?? '';
            $row[] = $m['statuses'] ?? '';
            $row[] = \App\Support\GenotypeSource::labelList($m['sources'] ?? null);
            fputcsv($out, $row, ';');
        }
        fclose($out);
        exit;
    }

    public function manualStore(): string
    {
        Csrf::verify();
        $dogId = (int) input('dog_id');
        $dog = $dogId > 0 ? (new DogRepository())->find($dogId) : null;
        if ($dog === null) {
            Session::flash('genetics_error', t('Vyberte psa (napište jméno a vyberte z nabídky).'));
            redirect('/admin/genetics');
        }

        // Vsechny geny naraz: g[<marker_id>] = genotyp (prazdne se preskoci).
        $values = (array) ($_POST['g'] ?? []);
        $breedId = $dog['breed_id'] !== null ? (int) $dog['breed_id'] : null;
        $source = \App\Support\GenotypeSource::normalize((string) input('source')) ?? \App\Support\GenotypeSource::DEFAULT;
        $genotypes = new GenotypeRepository();
        $saved = 0;
        $invalid = [];
        foreach ($values as $markerId => $raw) {
            $markerId = (int) $markerId;
            $value = trim((string) $raw);
            if ($markerId <= 0 || $value === '') {
                continue;
            }
            $split = Genetics::splitGenotype($value);
            if ($split === null) {
                $invalid[] = $value;
                continue;
            }
            $genotypes->upsertGenotype($dogId, $breedId, $markerId, $split['allele_1'], $split['allele_2'], $split['genotype'], null, 'manual', null, $source);
            $saved++;
        }

        if ($saved > 0) {
            (new \App\Repositories\SampleRepository())->markAnalysisDoneForDog($dogId);
            AuditService::log(Auth::id(), Auth::role(), 'genotype_manual', 'dog', (string) $dogId, null, ['saved' => $saved]);
        }

        if ($saved === 0 && $invalid === []) {
            Session::flash('genetics_error', t('Nezadali jste žádný genotyp.'));
        } elseif ($invalid !== []) {
            Session::flash('genetics_error', t('Uloženo: {saved} genů. Neplatný formát: {invalid}.', ['saved' => $saved, 'invalid' => implode(', ', $invalid)]));
        } else {
            Session::flash('genetics_notice', t('Uloženo genotypů: {saved}.', ['saved' => $saved]));
        }
        redirect('/admin/genetics');
    }

    public function markers(): string
    {
        $repo = new GeneRepository();
        return view('admin/genetics/markers', [
            'title' => 'Geny a markery',
            'genes' => $repo->genes(),
            'markers' => $repo->markers(),
            'notice' => Session::flash('genetics_notice'),
            'error' => Session::flash('genetics_error'),
        ]);
    }

    public function createGene(): string
    {
        Csrf::verify();
        $symbol = trim((string) input('symbol'));
        if ($symbol === '') {
            Session::flash('genetics_error', t('Zadejte symbol genu.'));
            redirect('/admin/genetics/markers');
        }
        (new GeneRepository())->createGene(
            $symbol,
            trim((string) input('name')) ?: null,
            trim((string) input('description')) ?: null,
            trim((string) input('note')) ?: null
        );
        Session::flash('genetics_notice', t('Gen přidán.'));
        redirect('/admin/genetics/markers');
    }

    public function createMarker(): string
    {
        Csrf::verify();
        $geneId = (int) input('gene_id');
        $code = trim((string) input('marker_code'));
        if ($geneId <= 0 || $code === '') {
            Session::flash('genetics_error', t('Vyberte gen a zadejte kód markeru.'));
            redirect('/admin/genetics/markers');
        }
        (new GeneRepository())->createMarker(
            $geneId,
            $code,
            trim((string) input('locus')) ?: null,
            trim((string) input('reference_allele')) ?: null,
            trim((string) input('alternate_allele')) ?: null,
            trim((string) input('allowed_values')) ?: null
        );
        Session::flash('genetics_notice', t('Marker přidán.'));
        redirect('/admin/genetics/markers');
    }

    public function editGene(string $id): string
    {
        $gene = (new GeneRepository())->findGene((int) $id);
        if ($gene === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Gen nenalezen']);
        }
        return view('admin/genetics/gene_edit', [
            'title' => 'Upravit gen',
            'gene' => $gene,
            'error' => Session::flash('genetics_error'),
        ]);
    }

    public function updateGene(string $id): string
    {
        Csrf::verify();
        $symbol = trim((string) input('symbol'));
        if ($symbol === '') {
            Session::flash('genetics_error', t('Zadejte symbol genu.'));
            redirect('/admin/genetics/genes/' . $id . '/edit');
        }
        (new GeneRepository())->updateGene(
            (int) $id,
            $symbol,
            trim((string) input('name')) ?: null,
            trim((string) input('description')) ?: null,
            trim((string) input('note')) ?: null
        );
        AuditService::log(Auth::id(), Auth::role(), 'gene_updated', 'gene', $id);
        Session::flash('genetics_notice', t('Gen uložen.'));
        redirect('/admin/genetics/markers');
    }

    public function editMarker(string $id): string
    {
        $repo = new GeneRepository();
        $marker = $repo->findMarker((int) $id);
        if ($marker === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Marker nenalezen']);
        }
        return view('admin/genetics/marker_edit', [
            'title' => 'Upravit marker',
            'marker' => $marker,
            'genes' => $repo->genes(),
            'error' => Session::flash('genetics_error'),
        ]);
    }

    public function updateMarker(string $id): string
    {
        Csrf::verify();
        $code = trim((string) input('marker_code'));
        $geneId = (int) input('gene_id');
        if ($code === '' || $geneId <= 0) {
            Session::flash('genetics_error', t('Vyberte gen a zadejte kód markeru.'));
            redirect('/admin/genetics/markers/' . $id . '/edit');
        }
        (new GeneRepository())->updateMarker(
            (int) $id,
            $geneId,
            $code,
            trim((string) input('locus')) ?: null,
            trim((string) input('reference_allele')) ?: null,
            trim((string) input('alternate_allele')) ?: null,
            trim((string) input('allowed_values')) ?: null
        );
        AuditService::log(Auth::id(), Auth::role(), 'marker_updated', 'genetic_marker', $id);
        Session::flash('genetics_notice', t('Marker uložen.'));
        redirect('/admin/genetics/markers');
    }

    public function importForm(): string
    {
        return view('admin/genetics/import', [
            'title' => 'Import genotypů',
            'preview' => null,
            'error' => Session::flash('genetics_error'),
        ]);
    }

    public function importPreview(): string
    {
        Csrf::verify();
        $file = $_FILES['file'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Session::flash('genetics_error', t('Nahrajte CSV soubor.'));
            redirect('/admin/genetics/import');
        }
        if ((int) $file['size'] > 2 * 1024 * 1024) {
            Session::flash('genetics_error', t('Soubor je příliš velký (max 2 MB).'));
            redirect('/admin/genetics/import');
        }

        $content = (string) file_get_contents((string) $file['tmp_name']);
        Session::put('genetics_csv', $content);
        $parsed = Csv::parse($content);
        $preview = (new GeneticsImportService())->preview($parsed);

        return view('admin/genetics/import', [
            'title' => 'Import genotypů - náhled',
            'preview' => $preview,
            'error' => null,
        ]);
    }

    public function importCommit(): string
    {
        Csrf::verify();
        $content = Session::get('genetics_csv');
        if (!is_string($content) || $content === '') {
            Session::flash('genetics_error', t('Relace importu vypršela, nahrajte soubor znovu.'));
            redirect('/admin/genetics/import');
        }
        $result = (new GeneticsImportService())->commit(Csv::parse($content), Auth::id());
        AuditService::log(Auth::id(), Auth::role(), 'genetics_import', 'genetics', null, null, $result);
        Session::forget('genetics_csv');

        Session::flash('genetics_notice', t('Import: {tests} testů, {genotypes} genotypů, {skipped} řádků přeskočeno (nenalezeny sample_id).', [
            'tests' => $result['tests'],
            'genotypes' => $result['genotypes'],
            'skipped' => $result['skipped'],
        ]));
        redirect('/admin/genetics');
    }

    public function template(): string
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="import_sablona_pcr_genetika.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, self::TEMPLATE_HEADER);
        fclose($out);
        exit;
    }
}
