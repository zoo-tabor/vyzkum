<?php
/** @var array<int, array<string, mixed>> $breeds */
/** @var int $selected */
/** @var array{total:int, alive:int, dead:int} $counts */
/** @var float|null $avgAge */
/** @var array{b0:int,b1:int,b2:int,b3:int} $buckets */
/** @var array<int, array{cause:string, c:int}> $deathCauses */
/** @var array<int, array{gene_symbol:string, marker_code:string, genotype:string, c:int}> $genetics */
?>
<div class="page-head"><h1>Klubovy prehled</h1></div>

<?php if ($breeds === []): ?>
    <div class="card"><p>Vas ucet zatim nema prirazene zadne plemeno. Kontaktujte vyzkumny tym.</p></div>
<?php else: ?>
    <form method="get" action="/club" class="card filters">
        <label for="breed">Plemeno:</label>
        <select id="breed" name="breed" onchange="this.form.submit()">
            <?php foreach ($breeds as $b): ?>
                <option value="<?= (int) $b['id'] ?>"<?= $selected === (int) $b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <noscript><button class="btn" type="submit">Zobrazit</button></noscript>
    </form>

    <div class="cards">
        <div class="card stat"><div class="stat__value"><?= (int) $counts['total'] ?></div><div class="stat__label">Psi celkem</div></div>
        <div class="card stat"><div class="stat__value"><?= (int) $counts['alive'] ?></div><div class="stat__label">Zivi</div></div>
        <div class="card stat"><div class="stat__value"><?= (int) $counts['dead'] ?></div><div class="stat__label">Uhynuli</div></div>
        <div class="card stat"><div class="stat__value"><?= $avgAge !== null ? e((string) $avgAge) : '-' ?></div><div class="stat__label">Prumerny vek (let)</div></div>
    </div>

    <div class="card">
        <h2>Vekova struktura</h2>
        <table class="table">
            <thead><tr><th>&lt; 1 rok</th><th>1-3 roky</th><th>3-7 let</th><th>7+ let</th></tr></thead>
            <tbody><tr>
                <td><?= (int) $buckets['b0'] ?></td><td><?= (int) $buckets['b1'] ?></td>
                <td><?= (int) $buckets['b2'] ?></td><td><?= (int) $buckets['b3'] ?></td>
            </tr></tbody>
        </table>
    </div>

    <div class="card">
        <h2>Priciny umrti</h2>
        <?php if ($deathCauses === []): ?><p class="muted">Zadne zaznamy.</p><?php else: ?>
            <table class="table">
                <thead><tr><th>Pricina</th><th>Pocet</th></tr></thead>
                <tbody>
                <?php foreach ($deathCauses as $d): ?>
                    <tr><td><?= e($d['cause']) ?></td><td><?= (int) $d['c'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Geneticke rozlozeni</h2>
        <?php if ($genetics === []): ?><p class="muted">Zadna geneticka data.</p><?php else: ?>
            <table class="table">
                <thead><tr><th>Gen / marker</th><th>Genotyp</th><th>Pocet</th></tr></thead>
                <tbody>
                <?php foreach ($genetics as $g): ?>
                    <tr><td><?= e($g['gene_symbol']) ?> / <code><?= e($g['marker_code']) ?></code></td><td><strong><?= e($g['genotype']) ?></strong></td><td><?= (int) $g['c'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>
