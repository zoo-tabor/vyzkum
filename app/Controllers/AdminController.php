<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Csrf;
use App\Repositories\SampleRepository;
use App\Repositories\VetRepository;
use App\Services\AdminAuth;
use App\Services\DatabaseMigrationService;

final class AdminController
{
    private ?SampleRepository $samples = null;
    private ?VetRepository $vets = null;
    private ?DatabaseMigrationService $migration = null;
    private AdminAuth $auth;
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->auth = new AdminAuth($config);
    }

    public function index(): string
    {
        $this->auth->requireAdmin();
        try {
            if ($this->migrationService()->tables() === []) {
                redirect('/admin/migrate');
            }

            $samples = $this->sampleRepo()->all();
            $vets = $this->vetRepo()->all();
        } catch (\Throwable $e) {
            return view('admin/migration', [
                'title' => 'Migrace databáze',
                'tables' => [],
                'error' => $e->getMessage(),
                'migrated' => false,
                'vetChamberNumberExists' => false,
                'admin' => true,
            ]);
        }

        return view('admin/index', [
            'title' => 'Administrace',
            'samples' => $samples,
            'vets' => $vets,
            'admin' => true,
        ]);
    }

    public function vets(): string
    {
        $this->auth->requireAdmin();
        return view('admin/vets', [
            'title' => 'Veterinari',
            'vets' => $this->vetRepo()->all(),
            'admin' => true,
        ]);
    }

    public function createVet(): never
    {
        $this->auth->requireAdmin();
        Csrf::verify();

        $name = trim((string) input('name'));
        if ($name !== '') {
            $this->vetRepo()->create($_POST);
        }

        redirect('/admin/vets');
    }

    public function newBatch(): string
    {
        $this->auth->requireAdmin();
        if ($this->missingBatchStorageObjects() !== []) {
            redirect('/admin/migrate');
        }

        return view('admin/new_batch', [
            'title' => 'Nova davka vzorku',
            'vets' => $this->vetRepo()->all(),
            'admin' => true,
        ]);
    }

    public function createBatch(): string
    {
        $this->auth->requireAdmin();
        Csrf::verify();
        if ($this->missingBatchStorageObjects() !== []) {
            redirect('/admin/migrate');
        }

        $count = max(1, min(200, (int) input('count', 20)));
        $vetId = (int) input('vet_id', 0);
        $result = $this->sampleRepo()->createBatch(
            $count,
            $vetId > 0 ? $vetId : null,
            (string) $this->config->get('APP_URL', 'http://localhost:8000'),
            (string) input('label', '')
        );

        return view('admin/print_labels', [
            'title' => 'Tisk stitku',
            'batch' => $result['batch'],
            'rows' => $result['rows'],
            'qrLabels' => true,
            'admin' => true,
        ]);
    }

    public function batches(): string
    {
        $this->auth->requireAdmin();
        if ($this->missingBatchStorageObjects() !== []) {
            redirect('/admin/migrate');
        }

        return view('admin/batches', [
            'title' => 'Davky vzorku',
            'batches' => $this->sampleRepo()->batches(),
            'admin' => true,
        ]);
    }

    public function batchLabels(string $batchId): string
    {
        $this->auth->requireAdmin();
        if ($this->missingBatchStorageObjects() !== []) {
            redirect('/admin/migrate');
        }

        $result = $this->sampleRepo()->batchLabels(
            (int) $batchId,
            (string) $this->config->get('APP_URL', 'http://localhost:8000')
        );
        if (!$result) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Davka nenalezena', 'admin' => true]);
        }

        return view('admin/print_labels', [
            'title' => 'Tisk stitku',
            'batch' => $result['batch'],
            'rows' => $result['rows'],
            'qrLabels' => true,
            'admin' => true,
        ]);
    }

    public function migration(): string
    {
        $this->auth->requireAdmin();

        $error = null;
        $tables = [];
        try {
            $tables = $this->migrationService()->tables();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return view('admin/migration', [
            'title' => 'Migrace databáze',
            'tables' => $tables,
            'error' => $error,
            'migrated' => false,
            'vetChamberNumberExists' => $this->vetChamberNumberExists(),
            'missingBatchStorageObjects' => $this->missingBatchStorageObjects(),
            'admin' => true,
        ]);
    }

    public function runMigration(): string
    {
        $this->auth->requireAdmin();
        Csrf::verify();

        $error = null;
        $tables = [];
        $migrated = false;

        try {
            $tables = $this->migrationService()->migrate(ROOT_PATH . '/database/schema.sql');
            $migrated = true;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            try {
                $tables = $this->migrationService()->tables();
            } catch (\Throwable) {
                $tables = [];
            }
        }

        return view('admin/migration', [
            'title' => 'Migrace databáze',
            'tables' => $tables,
            'error' => $error,
            'migrated' => $migrated,
            'vetChamberNumberExists' => $this->vetChamberNumberExists(),
            'missingBatchStorageObjects' => $this->missingBatchStorageObjects(),
            'admin' => true,
        ]);
    }

    public function installBatchStorage(): string
    {
        $this->auth->requireAdmin();
        Csrf::verify();

        $error = null;
        $tables = [];
        $batchStorageInstalled = false;

        try {
            $this->migrationService()->installBatchStorage();
            $batchStorageInstalled = true;
            $tables = $this->migrationService()->tables();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            try {
                $tables = $this->migrationService()->tables();
            } catch (\Throwable) {
                $tables = [];
            }
        }

        return view('admin/migration', [
            'title' => 'Migrace databáze',
            'tables' => $tables,
            'error' => $error,
            'migrated' => false,
            'batchStorageInstalled' => $batchStorageInstalled,
            'vetChamberNumberExists' => $this->vetChamberNumberExists(),
            'missingBatchStorageObjects' => $this->missingBatchStorageObjects(),
            'admin' => true,
        ]);
    }

    public function dropVetChamberNumber(): string
    {
        $this->auth->requireAdmin();
        Csrf::verify();

        $error = null;
        $tables = [];
        $columnDropped = false;

        try {
            $this->migrationService()->dropVetChamberNumber();
            $columnDropped = true;
            $tables = $this->migrationService()->tables();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            try {
                $tables = $this->migrationService()->tables();
            } catch (\Throwable) {
                $tables = [];
            }
        }

        return view('admin/migration', [
            'title' => 'Migrace databáze',
            'tables' => $tables,
            'error' => $error,
            'migrated' => false,
            'columnDropped' => $columnDropped,
            'vetChamberNumberExists' => $this->vetChamberNumberExists(),
            'missingBatchStorageObjects' => $this->missingBatchStorageObjects(),
            'admin' => true,
        ]);
    }

    public function detail(string $sampleId): string
    {
        $this->auth->requireAdmin();
        $sample = $this->sampleRepo()->detail($sampleId);
        if (!$sample) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Vzorek nenalezen', 'admin' => true]);
        }

        return view('admin/detail', ['title' => 'Detail vzorku', 'sample' => $sample, 'admin' => true]);
    }

    public function updateStatus(string $sampleId): never
    {
        $this->auth->requireAdmin();
        Csrf::verify();
        $allowed = [
            'created', 'assigned_to_vet', 'vet_submitted', 'owner_submitted', 'sample_received',
            'pedigree_checked', 'data_validated', 'excluded', 'analysis_ready', 'analysis_done',
            'result_available', 'followup_needed', 'deceased_reported',
        ];
        $status = (string) input('status');
        if (in_array($status, $allowed, true)) {
            $this->sampleRepo()->updateStatus($sampleId, $status);
        }
        redirect('/admin/samples/' . rawurlencode($sampleId));
    }

    public function exportSamples(): never
    {
        $this->auth->requireAdmin();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="samples.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['sample_id', 'status', 'chip', 'dog_name', 'breed', 'owner_email', 'collection_date', 'sample_type']);
        foreach ($this->sampleRepo()->all() as $sample) {
            fputcsv($out, [
                $sample['sample_id'],
                $sample['status'],
                $sample['chip_number_vet'],
                $sample['dog_name'],
                $sample['breed'],
                $sample['owner_email'],
                $sample['collection_date'],
                $sample['sample_type'],
            ]);
        }
        fclose($out);
        exit;
    }

    private function sampleRepo(): SampleRepository
    {
        return $this->samples ??= new SampleRepository();
    }

    private function vetRepo(): VetRepository
    {
        return $this->vets ??= new VetRepository();
    }

    private function migrationService(): DatabaseMigrationService
    {
        return $this->migration ??= new DatabaseMigrationService();
    }

    private function vetChamberNumberExists(): bool
    {
        try {
            return $this->migrationService()->vetChamberNumberExists();
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array<int, string> */
    private function missingBatchStorageObjects(): array
    {
        try {
            return $this->migrationService()->missingBatchStorageObjects();
        } catch (\Throwable) {
            return [];
        }
    }
}
