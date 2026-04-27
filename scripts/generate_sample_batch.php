<?php
declare(strict_types=1);

$config = require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/helpers.php';

use App\Repositories\SampleRepository;

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

$count = isset($argv[1]) ? max(1, (int) $argv[1]) : 10;
$vetId = isset($argv[2]) ? (int) $argv[2] : null;
$label = $argv[3] ?? 'CLI ' . date('Y-m-d H:i');
$appUrl = rtrim((string) $config->get('APP_URL', 'http://localhost:8000'), '/');

$result = (new SampleRepository())->createBatch($count, $vetId ?: null, $appUrl, $label);

$out = fopen('php://output', 'w');
fputcsv($out, ['batch_id', $result['batch']['id']]);
fputcsv($out, ['sample_id', 'vet_url', 'owner_url']);
foreach ($result['rows'] as $row) {
    fputcsv($out, [$row['sample_id'], $row['vet_url'], $row['owner_url']]);
}
fclose($out);
