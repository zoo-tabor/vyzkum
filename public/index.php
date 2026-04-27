<?php
declare(strict_types=1);

$config = require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/helpers.php';

use App\Controllers\AdminController;
use App\Controllers\OwnerController;
use App\Controllers\VetController;
use App\Core\Router;

$router = new Router();

$router->get('/', fn () => view('home', ['title' => 'Evidence vzorku psu']));

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
$router->get('/admin/samples/new-batch', [$admin, 'newBatch']);
$router->post('/admin/samples/new-batch', [$admin, 'createBatch']);
$router->get('/admin/samples/{sampleId}', [$admin, 'detail']);
$router->post('/admin/samples/{sampleId}/status', [$admin, 'updateStatus']);
$router->get('/admin/export/samples.csv', [$admin, 'exportSamples']);

echo $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
