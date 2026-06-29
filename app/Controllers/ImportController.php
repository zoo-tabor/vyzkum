<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\DogOwnerImporter;
use App\Support\Csv;

final class ImportController
{
    private const MAX_BYTES = 2 * 1024 * 1024; // 2 MB

    private const TEMPLATE_HEADER = [
        'breed_slug', 'dog_name', 'kennel_name', 'sex', 'pedigree_number', 'chip_number',
        'birth_date', 'death_date', 'death_cause', 'color', 'test_group', 'health_summary',
        'owner_name', 'owner_primary_email', 'owner_secondary_emails', 'owner_phones',
        'owner_address', 'ownership_note', 'sample_id', 'sample_type', 'sample_received_at',
        'sample_material_count', 'notes',
    ];

    public function form(): string
    {
        return view('admin/import/form', [
            'title' => 'Import psu a majitelu',
            'preview' => null,
            'error' => Session::flash('import_error'),
        ]);
    }

    public function preview(): string
    {
        Csrf::verify();

        $file = $_FILES['file'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Session::flash('import_error', 'Nahrajte CSV soubor.');
            redirect('/admin/import');
        }
        if ((int) $file['size'] > self::MAX_BYTES) {
            Session::flash('import_error', 'Soubor je prilis velky (max 2 MB).');
            redirect('/admin/import');
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'], true)) {
            Session::flash('import_error', 'Povolen je jen CSV soubor.');
            redirect('/admin/import');
        }

        $content = (string) file_get_contents((string) $file['tmp_name']);
        Session::put('import_csv', $content);
        Session::put('import_name', basename((string) $file['name']));

        $parsed = Csv::parse($content);
        $preview = (new DogOwnerImporter())->preview($parsed['rows']);

        return view('admin/import/form', [
            'title' => 'Import - nahled',
            'preview' => $preview,
            'header' => $parsed['header'],
            'name' => Session::get('import_name'),
            'error' => null,
        ]);
    }

    public function commit(): string
    {
        Csrf::verify();

        $content = Session::get('import_csv');
        if (!is_string($content) || $content === '') {
            Session::flash('import_error', 'Relace importu vyprsela, nahrajte soubor znovu.');
            redirect('/admin/import');
        }

        $parsed = Csv::parse($content);
        $result = (new DogOwnerImporter())->commit($parsed['rows'], (int) Auth::id());

        AuditService::log(Auth::id(), Auth::role(), 'import_committed', 'import', null, null, $result);
        Session::forget('import_csv');
        Session::forget('import_name');

        Session::flash('dog_notice', sprintf(
            'Import dokoncen: %d psu, %d novych majitelu (%d znovu pouzito), %d radku preskoceno.',
            $result['dogs'],
            $result['owners_created'],
            $result['owners_reused'],
            $result['skipped']
        ));
        redirect('/admin/dogs');
    }

    public function template(): string
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="import_sablona_psi_majitele_vzorky.csv"');
        echo "\xEF\xBB\xBF"; // BOM pro Excel
        $out = fopen('php://output', 'w');
        fputcsv($out, self::TEMPLATE_HEADER);
        fclose($out);
        exit;
    }
}
