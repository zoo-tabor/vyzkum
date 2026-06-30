<?php
/** @var array<string, int> $stats */
/** @var int|null $currentBreedId */
?>
<div class="page-head">
    <h1>Dashboard</h1>
    <p class="muted">
        Kontext plemene:
        <strong><?= $currentBreedId === null ? 'Všechna plemena' : ('#' . (int) $currentBreedId) ?></strong>
    </p>
</div>

<div class="cards">
    <div class="card stat">
        <div class="stat__value"><?= (int) $stats['breeds'] ?></div>
        <div class="stat__label">Plemena</div>
    </div>
    <div class="card stat">
        <div class="stat__value"><?= (int) $stats['users'] ?></div>
        <div class="stat__label">Uživatelé</div>
    </div>
    <div class="card stat">
        <div class="stat__value"><?= (int) $stats['owners'] ?></div>
        <div class="stat__label">Majitelé</div>
    </div>
    <div class="card stat">
        <div class="stat__value"><?= (int) $stats['clubs'] ?></div>
        <div class="stat__label">Klubové účty</div>
    </div>
</div>
