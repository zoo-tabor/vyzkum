<?php
/** @var array<string, int> $stats */
/** @var int|null $currentBreedId */
?>
<div class="page-head">
    <h1>Dashboard</h1>
    <p class="muted">
        Kontext plemene:
        <strong><?= $currentBreedId === null ? 'Vsechna plemena' : ('#' . (int) $currentBreedId) ?></strong>
    </p>
</div>

<div class="cards">
    <div class="card stat">
        <div class="stat__value"><?= (int) $stats['breeds'] ?></div>
        <div class="stat__label">Plemena</div>
    </div>
    <div class="card stat">
        <div class="stat__value"><?= (int) $stats['users'] ?></div>
        <div class="stat__label">Uzivatele</div>
    </div>
    <div class="card stat">
        <div class="stat__value"><?= (int) $stats['owners'] ?></div>
        <div class="stat__label">Majitele</div>
    </div>
    <div class="card stat">
        <div class="stat__value"><?= (int) $stats['clubs'] ?></div>
        <div class="stat__label">Klubove ucty</div>
    </div>
</div>

<div class="card">
    <h2>Fáze 1 hotová</h2>
    <p class="muted">
        Bezi skelet, prihlaseni, role, prepinac plemene a audit log.
        Dalsi moduly (psi, majitele, vzorky, formulare, genetika) prijdou v
        nasledujicich fazich podle <code>PLAN_VYVOJE.md</code>.
    </p>
</div>
