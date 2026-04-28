<?php
declare(strict_types=1);

$config = require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/helpers.php';

use App\Controllers\AdminController;
use App\Controllers\OwnerController;
use App\Controllers\VetController;
use App\Core\Router;

$router = new Router();

$router->get('/', fn () => view('home', ['title' => 'Evidence vzorků psů']));

$vet = new VetController();
$router->get('/vet/{sampleId}/{token}', [$vet, 'show']);
$router->post('/vet/{sampleId}/{token}', [$vet, 'submit']);

$owner = new OwnerController();
$router->get('/dog/{sampleId}/{token}', [$owner, 'show']);
$router->post('/dog/{sampleId}/{token}', [$owner, 'submit']);

$admin = new AdminController($config);
$router->get('/admin', [$admin, 'index']);
$router->get('/admin/vets', [$admin, 'vets']);
$router->post('/admin/vets', [$admin, 'createVet']);
$router->get('/admin/migrate', [$admin, 'migration']);
$router->post('/admin/migrate', [$admin, 'runMigration']);
$router->post('/admin/migrate/drop-vet-chamber-number', [$admin, 'dropVetChamberNumber']);
$router->post('/admin/migrate/install-batch-storage', [$admin, 'installBatchStorage']);
$router->post('/admin/migrate/install-owner-address', [$admin, 'installOwnerAddress']);
$router->get('/admin/batches', [$admin, 'batches']);
$router->get('/admin/batches/{batchId}/labels', [$admin, 'batchLabels']);
$router->get('/admin/samples/new-batch', [$admin, 'newBatch']);
$router->post('/admin/samples/new-batch', [$admin, 'createBatch']);
$router->get('/admin/samples/{sampleId}', [$admin, 'detail']);
$router->post('/admin/samples/{sampleId}/status', [$admin, 'updateStatus']);
$router->get('/admin/export/samples.csv', [$admin, 'exportSamples']);

try {
    echo $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (Throwable $e) {
    http_response_code(500);
    if ((bool) $config->get('APP_DEBUG', false)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Application error\n";
        echo "=================\n";
        echo $e::class . ': ' . $e->getMessage() . "\n";
        echo $e->getFile() . ':' . $e->getLine() . "\n\n";
        echo $e->getTraceAsString();
        exit;
    }

    echo 'Doslo k vnitrni chybe aplikace.';
}
