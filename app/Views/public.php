<?php
/** @var string $content */
/** @var string|null $title */
$pageTitle = isset($title) ? $title . ' - Vyzkum Zoo Tabor' : 'Vyzkum Zoo Tabor';
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
    <div class="public-brand">Vyzkum <span>Zoo Tabor</span></div>
</header>
<main class="public-main">
    <?= $content ?>
</main>
</body>
</html>
