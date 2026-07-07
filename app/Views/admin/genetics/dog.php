<?php
/** @var array<string, mixed> $dog */
/** @var array<int, array{gene_id:int, symbol:string, marker_id:int}> $genePanel */
/** @var array<int, string> $current */
/** @var array<int, string> $currentNotes */
/** @var string|null $notice */
/** @var string|null $error */
$dogId = (int) $dog['id'];
?>
<div class="page-head">
    <h1><?= t('Genetika:') ?> <?= e($dog['name']) ?> <span class="muted">(<?= e(\App\Support\Breeds::translate($dog['breed_name'] ?? '')) ?>)</span></h1>
    <p>
        <a href="/admin/genetics">&larr; <?= t('Zpět na přehled') ?></a>
        &nbsp;·&nbsp;
        <a href="/admin/dogs/<?= $dogId ?>"><?= t('Karta psa') ?> &rarr;</a>
    </p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2><?= t('Genotypy') ?></h2>
    <?php if ($genePanel === []): ?>
        <p class="muted"><?= t('Nejsou založené žádné geny. Přidejte je v sekci {link}.', ['link' => '<a href="/admin/genetics/markers">' . t('Geny a markery') . '</a>']) ?></p>
    <?php else: ?>
        <form method="post" action="/admin/genetics/<?= $dogId ?>">
            <?= \App\Core\Csrf::field() ?>
            <table class="table">
                <thead><tr><th><?= t('Gen') ?></th><th><?= t('Genotyp') ?></th><th><?= t('Poznámka') ?></th></tr></thead>
                <tbody>
                <?php foreach ($genePanel as $g): ?>
                    <?php $mid = (int) $g['marker_id']; ?>
                    <tr>
                        <td><strong><?= e($g['symbol']) ?></strong></td>
                        <td>
                            <input type="text" name="g[<?= $mid ?>]"
                                   value="<?= e($current[$mid] ?? '') ?>"
                                   placeholder="GG" style="max-width:120px; margin:0;">
                        </td>
                        <td>
                            <input type="text" name="n[<?= $mid ?>]"
                                   value="<?= e($currentNotes[$mid] ?? '') ?>"
                                   placeholder="<?= e(t('poznámka')) ?>" style="margin:0;">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="muted"><?= t('Prázdné pole genotypu u genu, který má hodnotu, záznam smaže. Formát např. {ex}; {x} = nevyšlá PCR sekvenace. Poznámka je nepovinná a váže se ke genotypu psa.', ['ex' => '<code>GG</code>, <code>GA</code>, <code>G/A</code>', 'x' => '<code>X</code>']) ?></p>

            <div class="form-row" style="margin-top:1rem;">
                <div>
                    <label for="tested_at"><?= t('Datum testu (nepovinné)') ?></label>
                    <input type="date" id="tested_at" name="tested_at">
                </div>
                <div></div>
                <div></div>
            </div>
            <p class="muted"><?= t('Datum se uloží k právě ukládaným genotypům.') ?></p>

            <button type="submit" class="btn btn--primary"><?= t('Uložit genetiku') ?></button>
        </form>
    <?php endif; ?>
</div>
