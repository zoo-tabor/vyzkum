<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\BreedRepository;
use App\Repositories\SampleRepository;
use App\Repositories\VetRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\BreedContext;

final class SampleController
{
    public const STATUSES = [
        'created', 'assigned_to_vet', 'vet_submitted', 'owner_submitted',
        'sample_received', 'data_validated', 'analysis_ready', 'analysis_done',
        'archived', 'excluded',
    ];

    public function index(): string
    {
        $repo = new SampleRepository();
        $breedId = BreedContext::current();
        $status = (string) input('status');

        return view('admin/samples/index', [
            'title' => 'Vzorky',
            'samples' => $repo->listForBreed($breedId, $status),
            'status' => $status,
            'statuses' => self::STATUSES,
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
            'title' => 'Nova davka vzorku',
            'breeds' => (new BreedRepository())->all(),
            'vets' => (new VetRepository())->all(),
            'currentBreedId' => BreedContext::current(),
            'error' => Session::flash('sample_error'),
        ]);
    }

    public function createBatch(): string
    {
        Csrf::verify();
        $count = (int) input('count');
        if ($count < 1 || $count > 200) {
            Session::flash('sample_error', 'Pocet sad musi byt 1 az 200.');
            redirect('/admin/samples/new-batch');
        }

        $breedId = (int) input('breed_id') ?: null;
        $vetId = (int) input('vet_id') ?: null;
        $appUrl = (string) Config::instance()->get('APP_URL', '');

        $result = (new SampleRepository())->createBatch($count, $breedId, $vetId, trim((string) input('label')) ?: null, $appUrl, Auth::id());
        AuditService::log(Auth::id(), Auth::role(), 'sample_batch_created', 'sample_batch', (string) $result['batch']['id'], null, ['count' => $count]);

        Session::flash('sample_notice', 'Davka vytvorena - vytisknete stitky.');
        redirect('/admin/batches/' . (int) $result['batch']['id'] . '/labels');
    }

    public function batches(): string
    {
        return view('admin/samples/batches', [
            'title' => 'Davky vzorku',
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
            return view('errors/404', ['title' => 'Davka nenalezena']);
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

    public function updateStatus(string $sampleId): string
    {
        Csrf::verify();
        $status = (string) input('status');
        if (!in_array($status, self::STATUSES, true)) {
            Session::flash('sample_error', 'Neplatny stav.');
            redirect('/admin/samples/' . $sampleId);
        }
        (new SampleRepository())->updateStatus($sampleId, $status);
        AuditService::log(Auth::id(), Auth::role(), 'sample_status_changed', 'sample', $sampleId, null, ['status' => $status]);
        Session::flash('sample_notice', 'Stav vzorku aktualizovan.');
        redirect('/admin/samples/' . $sampleId);
    }

    public function vets(): string
    {
        return view('admin/samples/vets', [
            'title' => 'Veterinari',
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
            Session::flash('sample_error', 'Zadejte jmeno veterinare.');
            redirect('/admin/vets');
        }
        (new VetRepository())->create(
            $name,
            trim((string) input('clinic_name')) ?: null,
            trim((string) input('email')) ?: null,
            trim((string) input('phone')) ?: null
        );
        Session::flash('sample_notice', 'Veterinar pridan.');
        redirect('/admin/vets');
    }
}
