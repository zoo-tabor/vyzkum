<?php
/** @var array<string, mixed> $dog */
/** @var array<int, array{gene_id:int, symbol:string, marker_id:int}> $genePanel */
/** @var array<int, string> $current */
/** @var string|null $notice */
/** @var string|null $error */
$dogId = (int) $dog['id'];
?>
<div class="page-head">
    <h1>Genetika: <?= e($dog['name']) ?> <span class="muted">(<?= e($dog['breed_name'] ?? '') ?>)</span></h1>
    <p>
        <a href="/admin/genetics">&larr; Zpět na přehled</a>
        &nbsp;·&nbsp;
        <a href="/admin/dogs/<?= $dogId ?>">Karta psa &rarr;</a>
    </p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Genotypy</h2>
    <?php if ($genePanel === []): ?>
        <p class="muted">Nejsou založené žádné geny. Přidejte je v sekci <a href="/admin/genetics/markers">Geny a markery</a>.</p>
    <?php else: ?>
        <form method="post" action="/admin/genetics/<?= $dogId ?>">
            <?= \App\Core\Csrf::field() ?>
            <table class="table">
                <thead><tr><th>Gen</th><th>Genotyp</th></tr></thead>
                <tbody>
                <?php foreach ($genePanel as $g): ?>
                    <tr>
                        <td><strong><?= e($g['symbol']) ?></strong></td>
                        <td>
                            <input type="text" name="g[<?= (int) $g['marker_id'] ?>]"
                                   value="<?= e($current[(int) $g['marker_id']] ?? '') ?>"
                                   placeholder="GG" style="max-width:120px; margin:0;">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="muted">Prázdné pole u genu, který má hodnotu, záznam smaže. Formát např. <code>GG</code>, <code>GA</code>, <code>G/A</code>; <code>X</code> = nevyšlá PCR sekvenace.</p>

            <div class="form-row" style="margin-top:1rem;">
                <div>
                    <label for="lab_name">Laboratoř (nepovinné)</label>
                    <input type="text" id="lab_name" name="lab_name">
                </div>
                <div>
                    <label for="tested_at">Datum testu (nepovinné)</label>
                    <input type="date" id="tested_at" name="tested_at">
                </div>
            </div>
            <p class="muted">Laboratoř a datum se uloží k právě ukládaným genotypům.</p>

            <button type="submit" class="btn btn--primary">Uložit genetiku</button>
        </form>
    <?php endif; ?>
</div>
