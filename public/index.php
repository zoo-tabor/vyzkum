<?php
declare(strict_types=1);

// Serve existing static files directly when using the PHP built-in server:
//   php -S localhost:8000 -t public public/index.php
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

$config = require dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\BreedController;
use App\Controllers\DashboardController;
use App\Controllers\DogController;
use App\Controllers\ImportController;
use App\Controllers\OwnerController;
use App\Controllers\TwoFactorController;
use App\Core\Router;
use App\Middleware\EnforceAdminTwoFactor;
use App\Middleware\RequireAuth;
use App\Middleware\RequireRole;

$router = new Router();

$router->get('/', fn () => redirect('/admin'));

$auth = new AuthController();
$router->get('/login', [$auth, 'showLogin']);
$router->post('/login', [$auth, 'login']);

// 2FA login challenge - user is not authenticated yet (login deferred).
$twoFactor = new TwoFactorController();
$router->get('/2fa', [$twoFactor, 'showChallenge']);
$router->post('/2fa', [$twoFactor, 'verifyChallenge']);

$router->group([RequireAuth::class, EnforceAdminTwoFactor::class], function (Router $router) use ($auth, $twoFactor): void {
    $router->post('/logout', [$auth, 'logout']);

    $dashboard = new DashboardController();
    $router->get('/admin', [$dashboard, 'index']);

    $breeds = new BreedController();
    $router->post('/admin/breed-context', [$breeds, 'switchContext']);

    $router->group([new RequireRole('research_admin')], function (Router $router) use ($breeds, $twoFactor): void {
        $router->get('/admin/breeds', [$breeds, 'index']);
        $router->post('/admin/breeds', [$breeds, 'create']);

        // Psi (poradi: staticke routy pred {id})
        $dogs = new DogController();
        $router->get('/admin/dogs', [$dogs, 'index']);
        $router->get('/admin/dogs/new', [$dogs, 'create']);
        $router->get('/admin/dogs/export.csv', [$dogs, 'export']);
        $router->post('/admin/dogs', [$dogs, 'store']);
        $router->get('/admin/dogs/{id}/edit', [$dogs, 'edit']);
        $router->post('/admin/dogs/{id}', [$dogs, 'update']);
        $router->get('/admin/dogs/{id}', [$dogs, 'show']);

        // Majitele
        $owners = new OwnerController();
        $router->get('/admin/owners', [$owners, 'index']);
        $router->get('/admin/owners/new', [$owners, 'create']);
        $router->post('/admin/owners', [$owners, 'store']);
        $router->get('/admin/owners/{id}', [$owners, 'show']);

        // Import CSV
        $import = new ImportController();
        $router->get('/admin/import', [$import, 'form']);
        $router->get('/admin/import/template.csv', [$import, 'template']);
        $router->post('/admin/import', [$import, 'preview']);
        $router->post('/admin/import/commit', [$import, 'commit']);

        $router->get('/admin/security', [$twoFactor, 'setup']);
        $router->post('/admin/security/enable', [$twoFactor, 'enable']);
        $router->post('/admin/security/disable', [$twoFactor, 'disable']);
        $router->post('/admin/security/password', [$twoFactor, 'changePassword']);
    });
});

try {
    echo $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (Throwable $e) {
    error_log($e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);

    if ((bool) $config->get('APP_DEBUG', false)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Application error\n=================\n";
        echo $e::class . ': ' . $e->getMessage() . "\n";
        echo $e->getFile() . ':' . $e->getLine() . "\n\n";
        echo $e->getTraceAsString();
        exit;
    }

    echo 'Doslo k vnitrni chybe aplikace.';
}
