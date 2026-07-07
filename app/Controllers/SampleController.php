<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\BreedRepository;
use App\Repositories\DogRepository;
use App\Repositories\SampleRepository;
use App\Repositories\VetRepository;
use App\Support\Gwas;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\BreedContext;

final class SampleController
{
    public const STATUSES = [
        'created', 'assigned_to_vet', 'vet_submitted', 'owner_submitted',
        'sample_received', 'data_validated', 'analysis_done',
    ];

    public function index(): string
    {
        $breedId = BreedContext::current();
        // Razeni/filtrovani/hledani (vc. dle jmena psa) + strankovani resi datatable.js.
        $rows = (new SampleRepository())->listForBreed($breedId, '', 1000000);

        return view('admin/samples/index', [
            'title' => 'Vzorky',
            'samples' => $rows,
            'currentBreedId' => $breedId,
            'notice' => Session::flash('sample_notice'),
            'error' => Session::flash('sample_error'),
        ]);
    }

    public function export(): never
    {
        $breedId = BreedContext::current();
        $rows = (new SampleRepository())->listForBreed($breedId, (string) input('status'), 100000);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="vzorky_export_' . date('Ymd_His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['sample_id', 'status', 'breed', 'dog', 'vet', 'sample_type', 'collection_date', 'created_at']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['sample_id'], $r['status'], $r['breed_name'] ?? '', $r['dog_name'] ?? '',
                $r['vet_name'] ?? '', $r['sample_type'] ?? '', $r['collection_date'] ?? '', $r['created_at'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function newBatch(): string
    {
        return view('admin/samples/new_batch', [
            'title' => 'Nová dávka vzorků',
            'vets' => (new VetRepository())->all(),
            'error' => Session::flash('sample_error'),
        ]);
    }

    public function createBatch(): string
    {
        Csrf::verify();
        $count = (int) input('count');
        if ($count < 1 || $count > 200) {
            Session::flash('sample_error', t('Počet sad musí být 1 až 200.'));
            redirect('/admin/samples/new-batch');
        }

        // Plemeno se nepřiřazuje - zadá ho majitel při registraci přes QR.
        $vetId = (int) input('vet_id') ?: null;
        $appUrl = (string) Config::instance()->get('APP_URL', '');

        $result = (new SampleRepository())->createBatch($count, null, $vetId, trim((string) input('label')) ?: null, $appUrl, Auth::id());
        AuditService::log(Auth::id(), Auth::role(), 'sample_batch_created', 'sample_batch', (string) $result['batch']['id'], null, ['count' => $count]);

        Session::flash('sample_notice', t('Dávka vytvořena - vytiskněte štítky.'));
        redirect('/admin/batches/' . (int) $result['batch']['id'] . '/labels');
    }

    public function manual(): string
    {
        return view('admin/samples/manual', [
            'title' => 'Ruční vzorek',
            'breeds' => (new BreedRepository())->all(),
            'currentBreedId' => BreedContext::current(),
            'error' => Session::flash('sample_error'),
        ]);
    }

    public function addSample(): string
    {
        Csrf::verify();
        $sampleId = trim((string) input('sample_id'));
        if ($sampleId === '') {
            Session::flash('sample_error', t('Zadejte číslo vzorku.'));
            redirect('/admin/samples/manual');
        }

        try {
            (new SampleRepository())->addManualSample(
                $sampleId,
                (int) input('breed_id') ?: null,
                trim((string) input('received_at')) ?: null,
                (int) input('dog_id') ?: null
            );
        } catch (\PDOException $e) {
            Session::flash('sample_error', t('Vzorek s číslem {id} už existuje.', ['id' => $sampleId]));
            redirect('/admin/samples/manual');
        }

        AuditService::log(Auth::id(), Auth::role(), 'sample_manual', 'sample', $sampleId);
        Session::flash('sample_notice', t('Vzorek byl ručně přidán.'));
        redirect('/admin/samples');
    }

    /** Naseptavac psa (JSON) pro rucni prirazeni vzorku ke psovi. */
    public function dogSuggest(): never
    {
        $q = trim((string) input('q'));
        $items = $q === '' ? [] : (new DogRepository())->searchByName($q, BreedContext::current());

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($items, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function batches(): string
    {
        return view('admin/samples/batches', [
            'title' => 'Dávky vzorků',
            'batches' => (new SampleRepository())->batches(),
            'notice' => Session::flash('sample_notice'),
        ]);
    }

    public function batchLabels(string $batchId): string
    {
        $appUrl = (string) Config::instance()->get('APP_URL', '');
        $data = (new SampleRepository())->batchLabels((int) $batchId, $appUrl);
        if ($data === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Dávka nenalezena']);
        }

        // Samostatna tiskova stranka (bez admin chrome) - presny format etiket.
        return view('admin/samples/labels', [
            'batch' => $data['batch'],
            'rows' => $data['rows'],
            '_layout' => false,
        ]);
    }

    public function detail(string $sampleId): string
    {
        $sample = (new SampleRepository())->detail($sampleId);
        if ($sample === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Vzorek nenalezen']);
        }

        return view('admin/samples/detail', [
            'title' => $sample['sample_id'],
            'sample' => $sample,
            'statuses' => self::STATUSES,
            'notice' => Session::flash('sample_notice'),
        ]);
    }

    public function edit(string $sampleId): string
    {
        $sample = (new SampleRepository())->detail($sampleId);
        if ($sample === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Vzorek nenalezen']);
        }

        return view('admin/samples/edit', [
            'title' => 'Upravit vzorek',
            'sample' => $sample,
            'error' => Session::flash('sample_error'),
        ]);
    }

    public function update(string $sampleId): string
    {
        Csrf::verify();
        $sample = (new SampleRepository())->detail($sampleId);
        if ($sample === null) {
            redirect('/admin/samples');
        }

        $gwas = trim((string) input('gwas_status'));
        if ($gwas !== '' && !isset(Gwas::LABELS[$gwas])) {
            $gwas = '';
        }

        (new SampleRepository())->updateAnalysis(
            $sampleId,
            trim((string) input('dna_isolated_at')) ?: null,
            $gwas ?: null,
            trim((string) input('note')) ?: null
        );
        AuditService::log(Auth::id(), Auth::role(), 'sample_updated', 'sample', $sampleId);
        Session::flash('sample_notice', t('Vzorek byl upraven.'));
        redirect('/admin/samples/' . rawurlencode($sampleId));
    }

    public function updateStatus(string $sampleId): string
    {
        Csrf::verify();
        $status = (string) input('status');
        if (!in_array($status, self::STATUSES, true)) {
            Session::flash('sample_error', t('Neplatný stav.'));
            redirect('/admin/samples/' . $sampleId);
        }
        (new SampleRepository())->updateStatus($sampleId, $status);
        AuditService::log(Auth::id(), Auth::role(), 'sample_status_changed', 'sample', $sampleId, null, ['status' => $status]);
        Session::flash('sample_notice', t('Stav vzorku aktualizován.'));
        redirect('/admin/samples/' . $sampleId);
    }

    public function vets(): string
    {
        return view('admin/samples/vets', [
            'title' => 'Veterináři',
            'vets' => (new VetRepository())->all(),
            'notice' => Session::flash('sample_notice'),
            'error' => Session::flash('sample_error'),
        ]);
    }

    public function createVet(): string
    {
        Csrf::verify();
        $name = trim((string) input('name'));
        if ($name === '') {
            Session::flash('sample_error', t('Zadejte jméno veterináře.'));
            redirect('/admin/vets');
        }
        (new VetRepository())->create(
            $name,
            trim((string) input('clinic_name')) ?: null,
            trim((string) input('email')) ?: null,
            trim((string) input('phone')) ?: null
        );
        Session::flash('sample_notice', t('Veterinář přidán.'));
        redirect('/admin/vets');
    }
}
