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
use App\Controllers\ColourController;
use App\Controllers\DashboardController;
use App\Controllers\DogController;
use App\Controllers\FormController;
use App\Controllers\GeneticsController;
use App\Controllers\HealthController;
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

// Zapomenute heslo - verejne, posle odkaz na /set-password/{token}.
$forgot = new \App\Controllers\ForgotPasswordController();
$router->get('/forgot-password', [$forgot, 'show']);
$router->post('/forgot-password', [$forgot, 'submit']);

// Prepnuti jazyka rozhrani (verejne) - navrat zpet dle ?r=.
$router->get('/locale/{lang}', [new \App\Controllers\LocaleController(), 'switch']);

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

// Verejne zasady zpracovani osobnich udaju (GDPR) - odkaz ze souhlasu.
$router->get('/gdpr', fn () => view('legal/gdpr', ['title' => 'Informovaný souhlas se zpracováním osobních údajů', '_layout' => 'public']));

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
    $router->get('/portal/onboarding', [$portal, 'onboarding']);
    $router->post('/portal/onboarding', [$portal, 'onboardingSubmit']);
    $router->get('/portal/contacts', [$portal, 'contacts']);
    $router->post('/portal/contacts', [$portal, 'updateContacts']);
    $router->get('/portal/settings', [$portal, 'settings']);
    $router->post('/portal/settings/password', [$portal, 'changePassword']);
    $router->post('/portal/settings/consent', [$portal, 'updateConsent']);
    $router->get('/portal/messages', [$portal, 'messages']);
    $router->post('/portal/messages', [$portal, 'postMessage']);
    $router->get('/portal/messages/{ref}', [$portal, 'messagesThread']);
    $router->get('/portal/forms', [$portal, 'forms']);
    $router->get('/portal/forms/{id}', [$portal, 'formResponse']);
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
        $router->get('/admin/breeds/{id}/translations', [$breeds, 'translations']);
        $router->post('/admin/breeds/{id}/translations', [$breeds, 'saveTranslations']);

        // Psi (poradi: staticke routy pred {id})
        $dogs = new DogController();
        $router->get('/admin/dogs', [$dogs, 'index']);
        $router->get('/admin/dogs/new', [$dogs, 'create']);
        $router->get('/admin/dogs/suggest', [$dogs, 'suggest']);
        $router->get('/admin/dogs/export.csv', [$dogs, 'export']);
        $router->post('/admin/dogs', [$dogs, 'store']);
        $router->get('/admin/dogs/{id}/edit', [$dogs, 'edit']);
        $router->post('/admin/dogs/{id}/delete', [$dogs, 'destroy']);
        $router->post('/admin/dogs/{id}/owner', [$dogs, 'changeOwner']);
        $router->post('/admin/dogs/{id}', [$dogs, 'update']);
        $router->get('/admin/dogs/{id}', [$dogs, 'show']);

        // Majitele
        $owners = new OwnerController();
        $router->get('/admin/owners', [$owners, 'index']);
        $router->get('/admin/owners/new', [$owners, 'create']);
        $router->post('/admin/owners', [$owners, 'store']);
        $router->get('/admin/owners/{id}/edit', [$owners, 'edit']);
        $router->post('/admin/owners/{id}/send-password', [$owners, 'sendPassword']);
        $router->post('/admin/owners/{id}/delete', [$owners, 'destroy']);
        $router->post('/admin/owners/{id}', [$owners, 'update']);
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
        $router->get('/admin/samples/manual', [$samples, 'manual']);
        $router->post('/admin/samples/manual', [$samples, 'addSample']);
        $router->get('/admin/samples/dog-suggest', [$samples, 'dogSuggest']);
        $router->post('/admin/samples/{sampleId}/status', [$samples, 'updateStatus']);
        $router->post('/admin/samples/{sampleId}/delete', [$samples, 'destroy']);
        $router->get('/admin/samples/{sampleId}/edit', [$samples, 'edit']);
        $router->post('/admin/samples/{sampleId}/edit', [$samples, 'update']);
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
        $router->get('/admin/forms/{id}/translations', [$forms, 'translations']);
        $router->post('/admin/forms/{id}/translations', [$forms, 'saveTranslations']);
        $router->get('/admin/forms/{id}/send', [$forms, 'broadcastForm']);
        $router->post('/admin/forms/{id}/send', [$forms, 'sendBroadcast']);
        $router->post('/admin/forms/{id}/questions', [$forms, 'addQuestion']);
        $router->get('/admin/forms/{id}/questions/{qid}/edit', [$forms, 'editQuestion']);
        $router->post('/admin/forms/{id}/questions/{qid}', [$forms, 'updateQuestion']);
        $router->post('/admin/forms/{id}/questions/{qid}/delete', [$forms, 'deleteQuestion']);
        $router->post('/admin/forms/{id}/questions/{qid}/move', [$forms, 'moveQuestion']);

        // Zdravi (prehled zdravotnich udalosti)
        $router->get('/admin/health', [new HealthController(), 'index']);

        // Interni zpravy (vlakna ke psovi)
        $messages = new MessagesController();
        $router->get('/admin/messages', [$messages, 'index']);
        $router->get('/admin/messages/owner/{id}', [$messages, 'owner']);
        $router->get('/admin/messages/{id}', [$messages, 'show']);
        $router->post('/admin/messages/{id}/reply', [$messages, 'reply']);
        $router->post('/admin/messages/{id}/status', [$messages, 'setStatus']);

        // Genetika (PCR markery, genotypy)
        $genetics = new GeneticsController();
        $router->get('/admin/genetics', [$genetics, 'index']);
        $router->get('/admin/genetics/dog-suggest', [$genetics, 'dogSuggest']);
        $router->get('/admin/genetics/export.csv', [$genetics, 'export']);
        $router->get('/admin/genetics/markers', [$genetics, 'markers']);
        $router->post('/admin/genetics/genes', [$genetics, 'createGene']);
        $router->post('/admin/genetics/markers', [$genetics, 'createMarker']);
        $router->get('/admin/genetics/genes/{id}/edit', [$genetics, 'editGene']);
        $router->post('/admin/genetics/genes/{id}', [$genetics, 'updateGene']);
        $router->get('/admin/genetics/markers/{id}/edit', [$genetics, 'editMarker']);
        $router->post('/admin/genetics/markers/{id}', [$genetics, 'updateMarker']);
        $router->get('/admin/genetics/import', [$genetics, 'importForm']);
        $router->get('/admin/genetics/import/template.csv', [$genetics, 'template']);
        $router->post('/admin/genetics/import', [$genetics, 'importPreview']);
        $router->post('/admin/genetics/import/commit', [$genetics, 'importCommit']);
        $router->post('/admin/genetics/manual', [$genetics, 'manualStore']);
        $router->get('/admin/genetics/{id}', [$genetics, 'show']);
        $router->post('/admin/genetics/{id}', [$genetics, 'update']);

        // Barvy plemen (ciselnik)
        $colours = new ColourController();
        $router->get('/admin/colours', [$colours, 'index']);
        $router->post('/admin/colours', [$colours, 'create']);
        $router->post('/admin/colours/{id}/delete', [$colours, 'delete']);

        // Klubove ucty + pristup k plemenum
        $clubAdmin = new ClubAdminController();
        $router->get('/admin/clubs', [$clubAdmin, 'index']);
        $router->post('/admin/clubs', [$clubAdmin, 'create']);
        $router->post('/admin/clubs/{id}/breeds', [$clubAdmin, 'updateAccess']);
        $router->post('/admin/clubs/{id}/delete', [$clubAdmin, 'destroy']);

        $router->get('/admin/security', [$twoFactor, 'setup']);
        $router->post('/admin/security/enable', [$twoFactor, 'enable']);
        $router->post('/admin/security/disable', [$twoFactor, 'disable']);
        $router->post('/admin/security/password', [$twoFactor, 'changePassword']);

        $emailTemplates = new \App\Controllers\EmailTemplateController();
        $router->get('/admin/email-templates', [$emailTemplates, 'index']);
        $router->get('/admin/email-templates/{key}', [$emailTemplates, 'edit']);
        $router->post('/admin/email-templates/{key}', [$emailTemplates, 'save']);

        $diagnostics = new \App\Controllers\DiagnosticsController();
        $router->get('/admin/diagnostics/smtp', [$diagnostics, 'smtp']);
        $router->post('/admin/diagnostics/smtp/send-test', [$diagnostics, 'sendTest']);
    });
});

foreach (\App\Support\SecurityHeaders::all() as $headerName => $headerValue) {
    if (!headers_sent()) {
        header($headerName . ': ' . $headerValue);
    }
}

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

    // Samostatna 500 stranka (nezavisla na DB/layoutu, aby neselhala znovu).
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="cs"><head><meta charset="utf-8">'
        . '<title>Chyba</title><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<style>body{font-family:system-ui,Arial,sans-serif;max-width:520px;margin:4rem auto;padding:0 1rem;color:#1f2933}</style>'
        . '</head><body><h1>Došlo k chybě</h1>'
        . '<p>Omlouváme se, nastala vnitřní chyba aplikace. Zkuste to prosím později.</p>'
        . '<p><a href="/">Zpět na úvod</a></p></body></html>';
}
