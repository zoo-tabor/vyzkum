<?php
/** @var string $content */
/** @var string|null $title */
$pageTitle = isset($title) ? $title . ' - Výzkum Zoo Tábor' : 'Výzkum Zoo Tábor';
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
</head>
<body>
<header class="public-top">
    <div class="public-brand">Výzkum <span>Zoo Tábor</span></div>
</header>
<main class="public-main">
    <?= $content ?>
</main>
</body>
</html>
