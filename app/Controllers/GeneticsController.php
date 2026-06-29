<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\DogRepository;
use App\Repositories\GeneRepository;
use App\Repositories\GenotypeRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\BreedContext;
use App\Services\GeneticsImportService;
use App\Support\Csv;
use App\Support\Genetics;
use App\Support\Paginator;

final class GeneticsController
{
    private const PER_PAGE = 50;

    private const TEMPLATE_HEADER = [
        'sample_id', 'expected_phenotype', 'B3GALNT1_genotype', 'NLRP1_genotype',
        'PARP14_genotype', 'COL9A1_genotype', 'lab_name', 'tested_at', 'notes',
    ];

    public function index(): string
    {
        $repo = new GenotypeRepository();
        $breedId = BreedContext::current();
        $filters = [
            'marker_id' => (int) input('marker_id') ?: null,
            'genotype' => (string) input('genotype'),
            'q' => (string) input('q'),
        ];
        $orderBy = GenotypeRepository::orderBy((string) input('sort'), (string) input('dir'));

        $total = $repo->count($filters, $breedId);
        $pager = new Paginator($total, (int) input('page', 1), self::PER_PAGE);
        $rows = $repo->list($filters, $breedId, $orderBy, $pager->perPage, $pager->offset);

        return view('admin/genetics/index', [
            'title' => 'Genetika',
            'rows' => $rows,
            'pager' => $pager,
            'filters' => $filters,
            'sort' => (string) input('sort', 'dog'),
            'dir' => (string) input('dir', 'asc'),
            'markers' => (new GeneRepository())->markersForSelect(),
            'notice' => Session::flash('genetics_notice'),
            'error' => Session::flash('genetics_error'),
        ]);
    }

    public function export(): never
    {
        $repo = new GenotypeRepository();
        $breedId = BreedContext::current();
        $filters = [
            'marker_id' => (int) input('marker_id') ?: null,
            'genotype' => (string) input('genotype'),
            'q' => (string) input('q'),
        ];
        $rows = $repo->exportRows($filters, $breedId, GenotypeRepository::orderBy((string) input('sort'), (string) input('dir')));

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="genotypy_export_' . date('Ymd_His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['dog', 'breed', 'gene', 'marker', 'genotype', 'tested_at', 'lab', 'status']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['dog_name'], $r['breed_name'] ?? '', $r['gene_symbol'], $r['marker_code'],
                $r['genotype'], $r['tested_at'] ?? '', $r['lab_name'] ?? '', $r['validation_status'],
            ]);
        }
        fclose($out);
        exit;
    }

    public function manualStore(): string
    {
        Csrf::verify();
        $dogId = (int) input('dog_id');
        $markerId = (int) input('marker_id');
        $value = trim((string) input('genotype'));
        $dog = $dogId > 0 ? (new DogRepository())->find($dogId) : null;
        $split = Genetics::splitGenotype($value);

        if ($dog === null || $markerId <= 0 || $split === null) {
            Session::flash('genetics_error', 'Zadejte platne ID psa, marker a genotyp.');
            redirect('/admin/genetics');
        }

        (new GenotypeRepository())->upsertGenotype(
            $dogId,
            $dog['breed_id'] !== null ? (int) $dog['breed_id'] : null,
            $markerId,
            $split['allele_1'],
            $split['allele_2'],
            $split['genotype'],
            null,
            'manual'
        );
        AuditService::log(Auth::id(), Auth::role(), 'genotype_manual', 'dog', (string) $dogId, null, ['marker_id' => $markerId, 'genotype' => $split['genotype']]);
        Session::flash('genetics_notice', 'Genotyp ulozen.');
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
            Session::flash('genetics_error', 'Zadejte symbol genu.');
            redirect('/admin/genetics/markers');
        }
        (new GeneRepository())->createGene($symbol, trim((string) input('name')) ?: null, trim((string) input('description')) ?: null);
        Session::flash('genetics_notice', 'Gen pridan.');
        redirect('/admin/genetics/markers');
    }

    public function createMarker(): string
    {
        Csrf::verify();
        $geneId = (int) input('gene_id');
        $code = trim((string) input('marker_code'));
        if ($geneId <= 0 || $code === '') {
            Session::flash('genetics_error', 'Vyberte gen a zadejte kod markeru.');
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
        Session::flash('genetics_notice', 'Marker pridan.');
        redirect('/admin/genetics/markers');
    }

    public function importForm(): string
    {
        return view('admin/genetics/import', [
            'title' => 'Import genotypu',
            'preview' => null,
            'error' => Session::flash('genetics_error'),
        ]);
    }

    public function importPreview(): string
    {
        Csrf::verify();
        $file = $_FILES['file'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Session::flash('genetics_error', 'Nahrajte CSV soubor.');
            redirect('/admin/genetics/import');
        }
        if ((int) $file['size'] > 2 * 1024 * 1024) {
            Session::flash('genetics_error', 'Soubor je prilis velky (max 2 MB).');
            redirect('/admin/genetics/import');
        }

        $content = (string) file_get_contents((string) $file['tmp_name']);
        Session::put('genetics_csv', $content);
        $parsed = Csv::parse($content);
        $preview = (new GeneticsImportService())->preview($parsed);

        return view('admin/genetics/import', [
            'title' => 'Import genotypu - nahled',
            'preview' => $preview,
            'error' => null,
        ]);
    }

    public function importCommit(): string
    {
        Csrf::verify();
        $content = Session::get('genetics_csv');
        if (!is_string($content) || $content === '') {
            Session::flash('genetics_error', 'Relace importu vyprsela, nahrajte soubor znovu.');
            redirect('/admin/genetics/import');
        }
        $result = (new GeneticsImportService())->commit(Csv::parse($content), Auth::id());
        AuditService::log(Auth::id(), Auth::role(), 'genetics_import', 'genetics', null, null, $result);
        Session::forget('genetics_csv');

        Session::flash('genetics_notice', sprintf(
            'Import: %d testu, %d genotypu, %d radku preskoceno (nenalezeny sample_id).',
            $result['tests'],
            $result['genotypes'],
            $result['skipped']
        ));
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
