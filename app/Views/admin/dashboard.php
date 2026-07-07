<?php
/** @var array<string, int> $stats */
/** @var int|null $currentBreedId */
?>
<div class="page-head">
    <h1><?= t('Dashboard') ?></h1>
    <p class="muted">
        <?= t('Kontext plemene:') ?>
        <strong><?= $currentBreedId === null ? t('Všechna plemena') : ('#' . (int) $currentBreedId) ?></strong>
    </p>
</div>

<div class="cards">
    <div class="card stat">
        <div class="stat__value"><?= (int) $stats['breeds'] ?></div>
        <div class="stat__label"><?= t('Plemena') ?></div>
    </div>
    <div class="card stat">
        <div class="stat__value"><?= (int) $stats['users'] ?></div>
        <div class="stat__label"><?= t('Uživatelé') ?></div>
    </div>
    <div class="card stat">
        <div class="stat__value"><?= (int) $stats['owners'] ?></div>
        <div class="stat__label"><?= t('Majitelé') ?></div>
    </div>
    <div class="card stat">
        <div class="stat__value"><?= (int) $stats['clubs'] ?></div>
        <div class="stat__label"><?= t('Klubové účty') ?></div>
    </div>
</div>
