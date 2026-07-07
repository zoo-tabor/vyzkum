<?php
/** @var array<int, array<string, mixed>> $breeds */
/** @var int $selected */
/** @var array{total:int, alive:int, dead:int} $counts */
/** @var float|null $avgAge */
/** @var array{b0:int,b1:int,b2:int,b3:int} $buckets */
/** @var array<int, array{cause:string, c:int}> $deathCauses */
/** @var array<int, array{gene_symbol:string, marker_code:string, genotype:string, c:int}> $genetics */
/** @var array<int, array{event_type:string, c:int}> $healthFreq */
?>
<div class="page-head"><h1><?= t('Klubový přehled') ?></h1></div>

<?php if ($breeds === []): ?>
    <div class="card"><p><?= t('Váš účet zatím nemá přiřazené žádné plemeno. Kontaktujte výzkumný tým.') ?></p></div>
<?php else: ?>
    <form method="get" action="/club" class="card filters">
        <label for="breed"><?= t('Plemeno:') ?></label>
        <select id="breed" name="breed" onchange="this.form.submit()">
            <?php foreach ($breeds as $b): ?>
                <option value="<?= (int) $b['id'] ?>"<?= $selected === (int) $b['id'] ? ' selected' : '' ?>><?= e(\App\Support\Breeds::translate($b['name'])) ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><button class="btn" type="submit"><?= t('Zobrazit') ?></button></noscript>
    </form>

    <div class="cards">
        <div class="card stat"><div class="stat__value"><?= (int) $counts['total'] ?></div><div class="stat__label"><?= t('Psi celkem') ?></div></div>
        <div class="card stat"><div class="stat__value"><?= (int) $counts['alive'] ?></div><div class="stat__label"><?= t('Živí') ?></div></div>
        <div class="card stat"><div class="stat__value"><?= (int) $counts['dead'] ?></div><div class="stat__label"><?= t('Uhynulí') ?></div></div>
        <div class="card stat"><div class="stat__value"><?= $avgAge !== null ? e((string) $avgAge) : '-' ?></div><div class="stat__label"><?= t('Průměrný věk (let)') ?></div></div>
    </div>

    <div class="card">
        <h2><?= t('Věková struktura') ?></h2>
        <table class="table">
            <thead><tr><th><?= t('< 1 rok') ?></th><th><?= t('1-3 roky') ?></th><th><?= t('3-7 let') ?></th><th><?= t('7+ let') ?></th></tr></thead>
            <tbody><tr>
                <td><?= (int) $buckets['b0'] ?></td><td><?= (int) $buckets['b1'] ?></td>
                <td><?= (int) $buckets['b2'] ?></td><td><?= (int) $buckets['b3'] ?></td>
            </tr></tbody>
        </table>
    </div>

    <div class="card">
        <h2><?= t('Příčiny úmrtí') ?></h2>
        <?php if ($deathCauses === []): ?><p class="muted"><?= t('Žádné záznamy.') ?></p><?php else: ?>
            <table class="table">
                <thead><tr><th><?= t('Příčina') ?></th><th><?= t('Počet') ?></th></tr></thead>
                <tbody>
                <?php foreach ($deathCauses as $d): ?>
                    <tr><td><?= e($d['cause']) ?></td><td><?= (int) $d['c'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2><?= t('Zdravotní události (četnost)') ?></h2>
        <?php if ($healthFreq === []): ?><p class="muted"><?= t('Žádné zdravotní události.') ?></p><?php else: ?>
            <table class="table">
                <thead><tr><th><?= t('Typ události') ?></th><th><?= t('Počet') ?></th></tr></thead>
                <tbody>
                <?php foreach ($healthFreq as $h): ?>
                    <tr><td><?= e(\App\Support\HealthEventType::label($h['event_type'])) ?></td><td><?= (int) $h['c'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2><?= t('Genetické rozložení') ?></h2>
        <?php if ($genetics === []): ?><p class="muted"><?= t('Žádná genetická data.') ?></p><?php else: ?>
            <table class="table">
                <thead><tr><th><?= t('Gen / marker') ?></th><th><?= t('Genotyp') ?></th><th><?= t('Počet') ?></th></tr></thead>
                <tbody>
                <?php foreach ($genetics as $g): ?>
                    <tr><td><?= e($g['gene_symbol']) ?> / <code><?= e($g['marker_code']) ?></code></td><td><strong><?= e($g['genotype']) ?></strong></td><td><?= (int) $g['c'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>
