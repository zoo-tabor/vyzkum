<?php
/** @var string $content */
/** @var string|null $title */
$pageTitle = isset($title) ? t($title) . ' - ' . t('Výzkum ZOO Tábor') : t('Výzkum ZOO Tábor');
?>
<!DOCTYPE html>
<html lang="<?= e(\App\Support\I18n::locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" type="image/png" href="/favicon/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/favicon/favicon.svg">
    <link rel="shortcut icon" href="/favicon/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/favicon/site.webmanifest">
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
</head>
<body>
<header class="topbar">
    <div class="topbar__brand"><a href="/"><img class="topbar__logo" src="/favicon/favicon.svg" width="28" height="28" alt=""> <?= t('Výzkum <span>ZOO Tábor</span>') ?></a></div>
    <div class="topbar__user">
        <?php include ROOT_PATH . '/app/Views/partials/lang_switch.php'; ?>
    </div>
</header>
<main class="public-main">
    <?= $content ?>
</main>
</body>
</html>
