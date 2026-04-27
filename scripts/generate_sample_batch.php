<?php
declare(strict_types=1);

$config = require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/helpers.php';

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

$count = isset($argv[1]) ? max(1, (int) $argv[1]) : 10;
$vetId = isset($argv[2]) ? (int) $argv[2] : null;
$year = date('Y');
$appUrl = rtrim((string) $config->get('APP_URL', 'http://localhost:8000'), '/');
$pdo = Database::pdo();

$insert = $pdo->prepare("
    INSERT INTO samples (sample_id, vet_id, status, vet_token_hash, owner_token_hash)
    VALUES (:sample_id, :vet_id, :status, :vet_token_hash, :owner_token_hash)
");

$out = fopen('php://output', 'w');
fputcsv($out, ['sample_id', 'vet_url', 'owner_url']);

for ($i = 0; $i < $count; $i++) {
    $sampleId = null;
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $candidate = 'SMP-' . $year . '-' . strtoupper(bin2hex(random_bytes(4)));
        try {
            $vetToken = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
            $ownerToken = rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
            $insert->execute([
                'sample_id' => $candidate,
                'vet_id' => $vetId ?: null,
                'status' => $vetId ? 'assigned_to_vet' : 'created',
                'vet_token_hash' => hash('sha256', $vetToken),
                'owner_token_hash' => hash('sha256', $ownerToken),
            ]);
            $sampleId = $candidate;
            fputcsv($out, [
                $sampleId,
                $appUrl . '/vet/' . rawurlencode($sampleId) . '/' . rawurlencode($vetToken),
                $appUrl . '/dog/' . rawurlencode($sampleId) . '/' . rawurlencode($ownerToken),
            ]);
            break;
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
        }
    }

    if ($sampleId === null) {
        throw new RuntimeException('Nepodarilo se vytvorit unikatni sample_id.');
    }
}

fclose($out);
