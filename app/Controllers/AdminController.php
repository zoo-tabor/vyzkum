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
        return view('admin/index', [
            'title' => 'Administrace',
            'samples' => $this->sampleRepo()->all(),
            'vets' => $this->vetRepo()->all(),
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

        $count = max(1, min(200, (int) input('count', 20)));
        $vetId = (int) input('vet_id', 0);
        $rows = $this->sampleRepo()->createBatch(
            $count,
            $vetId > 0 ? $vetId : null,
            (string) $this->config->get('APP_URL', 'http://localhost:8000')
        );

        return view('admin/print_labels', [
            'title' => 'Tisk stitku',
            'rows' => $rows,
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
            'title' => 'Migrace databaze',
            'tables' => $tables,
            'error' => $error,
            'migrated' => false,
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
            'title' => 'Migrace databaze',
            'tables' => $tables,
            'error' => $error,
            'migrated' => $migrated,
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
}
