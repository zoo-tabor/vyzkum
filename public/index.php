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
use App\Controllers\ClubAdminController;
use App\Controllers\ClubController;
use App\Controllers\DashboardController;
use App\Controllers\DogController;
use App\Controllers\FormController;
use App\Controllers\GeneticsController;
use App\Controllers\ImportController;
use App\Controllers\MessagesController;
use App\Controllers\OwnerController;
use App\Controllers\PortalController;
use App\Controllers\SampleController;
use App\Controllers\SetPasswordController;
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

// Verejne QR formulare (token, bez prihlaseni) - kompatibilni s old_app URL.
$publicSamples = new \App\Controllers\PublicSampleController();
$router->get('/vet/{sampleId}/{token}', [$publicSamples, 'vetShow']);
$router->post('/vet/{sampleId}/{token}', [$publicSamples, 'vetSubmit']);
$router->get('/dog/{sampleId}/{token}', [$publicSamples, 'dogShow']);
$router->post('/dog/{sampleId}/{token}', [$publicSamples, 'dogSubmit']);

// Verejne potvrzeni prevodu psa novym majitelem.
$transfer = new \App\Controllers\TransferController();
$router->get('/transfer/{token}', [$transfer, 'show']);
$router->post('/transfer/{token}', [$transfer, 'confirm']);

// Nastaveni hesla pres pozvanku (verejne, validuje token).
$setPassword = new SetPasswordController();
$router->get('/set-password/{token}', [$setPassword, 'show']);
$router->post('/set-password/{token}', [$setPassword, 'submit']);

// Klubovy dashboard (read-only, jen prirazena plemena).
$router->group([new RequireRole('club_viewer')], function (Router $router): void {
    $club = new ClubController();
    $router->get('/club', [$club, 'index']);
    $router->get('/club/dogs', [$club, 'dogs']);
});

// Portal majitele.
$router->group([new RequireRole('owner')], function (Router $router): void {
    $portal = new PortalController();
    $router->get('/portal', [$portal, 'index']);
    $router->get('/portal/contacts', [$portal, 'contacts']);
    $router->post('/portal/contacts', [$portal, 'updateContacts']);
    $router->get('/portal/dogs/{id}', [$portal, 'dog']);
    $router->post('/portal/dogs/{id}/confirm', [$portal, 'confirm']);
    $router->post('/portal/dogs/{id}/death', [$portal, 'death']);
    $router->post('/portal/dogs/{id}/document', [$portal, 'uploadDocument']);
    $router->get('/portal/dogs/{id}/forms/{defId}', [$portal, 'fillForm']);
    $router->post('/portal/dogs/{id}/forms/{defId}', [$portal, 'submitForm']);
    $router->post('/portal/dogs/{id}/message', [$portal, 'sendMessage']);
    $router->post('/portal/dogs/{id}/transfer', [$portal, 'transferRequest']);
});

// Stahovani souboru (admin i majitel; autorizace v controlleru).
$router->group([RequireAuth::class], function (Router $router): void {
    $router->get('/files/{id}', [new \App\Controllers\FileDownloadController(), 'download']);
});

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
        $router->post('/admin/owners/{id}/send-password', [$owners, 'sendPassword']);
        $router->get('/admin/owners/{id}', [$owners, 'show']);

        // Import CSV
        $import = new ImportController();
        $router->get('/admin/import', [$import, 'form']);
        $router->get('/admin/import/template.csv', [$import, 'template']);
        $router->post('/admin/import', [$import, 'preview']);
        $router->post('/admin/import/commit', [$import, 'commit']);

        // Vzorky / davky / veterinari (QR modul)
        $samples = new SampleController();
        $router->get('/admin/samples', [$samples, 'index']);
        $router->get('/admin/samples/export.csv', [$samples, 'export']);
        $router->get('/admin/samples/new-batch', [$samples, 'newBatch']);
        $router->post('/admin/samples/new-batch', [$samples, 'createBatch']);
        $router->post('/admin/samples/{sampleId}/status', [$samples, 'updateStatus']);
        $router->get('/admin/samples/{sampleId}', [$samples, 'detail']);
        $router->get('/admin/batches', [$samples, 'batches']);
        $router->get('/admin/batches/{batchId}/labels', [$samples, 'batchLabels']);
        $router->get('/admin/vets', [$samples, 'vets']);
        $router->post('/admin/vets', [$samples, 'createVet']);

        // Formulare (builder)
        $forms = new FormController();
        $router->get('/admin/forms', [$forms, 'index']);
        $router->post('/admin/forms', [$forms, 'create']);
        $router->get('/admin/forms/responses/{id}', [$forms, 'response']);
        $router->get('/admin/forms/{id}', [$forms, 'show']);
        $router->post('/admin/forms/{id}/publish', [$forms, 'publish']);
        $router->post('/admin/forms/{id}/new-version', [$forms, 'newVersion']);
        $router->post('/admin/forms/{id}/questions', [$forms, 'addQuestion']);
        $router->get('/admin/forms/{id}/questions/{qid}/edit', [$forms, 'editQuestion']);
        $router->post('/admin/forms/{id}/questions/{qid}', [$forms, 'updateQuestion']);
        $router->post('/admin/forms/{id}/questions/{qid}/delete', [$forms, 'deleteQuestion']);
        $router->post('/admin/forms/{id}/questions/{qid}/move', [$forms, 'moveQuestion']);

        // Interni zpravy (vlakna ke psovi)
        $messages = new MessagesController();
        $router->get('/admin/messages', [$messages, 'index']);
        $router->get('/admin/messages/{id}', [$messages, 'show']);
        $router->post('/admin/messages/{id}/reply', [$messages, 'reply']);
        $router->post('/admin/messages/{id}/status', [$messages, 'setStatus']);

        // Genetika (PCR markery, genotypy)
        $genetics = new GeneticsController();
        $router->get('/admin/genetics', [$genetics, 'index']);
        $router->get('/admin/genetics/export.csv', [$genetics, 'export']);
        $router->get('/admin/genetics/markers', [$genetics, 'markers']);
        $router->post('/admin/genetics/genes', [$genetics, 'createGene']);
        $router->post('/admin/genetics/markers', [$genetics, 'createMarker']);
        $router->get('/admin/genetics/import', [$genetics, 'importForm']);
        $router->get('/admin/genetics/import/template.csv', [$genetics, 'template']);
        $router->post('/admin/genetics/import', [$genetics, 'importPreview']);
        $router->post('/admin/genetics/import/commit', [$genetics, 'importCommit']);
        $router->post('/admin/genetics/manual', [$genetics, 'manualStore']);

        // Klubove ucty + pristup k plemenum
        $clubAdmin = new ClubAdminController();
        $router->get('/admin/clubs', [$clubAdmin, 'index']);
        $router->post('/admin/clubs', [$clubAdmin, 'create']);
        $router->post('/admin/clubs/{id}/breeds', [$clubAdmin, 'updateAccess']);

        $router->get('/admin/security', [$twoFactor, 'setup']);
        $router->post('/admin/security/enable', [$twoFactor, 'enable']);
        $router->post('/admin/security/disable', [$twoFactor, 'disable']);
        $router->post('/admin/security/password', [$twoFactor, 'changePassword']);

        $diagnostics = new \App\Controllers\DiagnosticsController();
        $router->get('/admin/diagnostics/smtp', [$diagnostics, 'smtp']);
        $router->post('/admin/diagnostics/smtp/send-test', [$diagnostics, 'sendTest']);
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
