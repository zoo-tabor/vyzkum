<?php
/** @var array{rows:array<int,array<string,mixed>>, summary:array<string,int>, markerColumns:array<int,array{column:string,code:string}>}|null $preview */
/** @var string|null $error */
?>
<div class="page-head">
    <h1><?= t('Import genotypů (PCR CSV)') ?></h1>
    <p><a href="/admin/genetics">&larr; <?= t('Zpět na genotypy') ?></a></p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2><?= t('Nahrát CSV') ?></h2>
    <p class="muted">
        <?= t('Široký formát: <code>sample_id</code>, <code>expected_phenotype</code>, sloupce <code>&lt;MARKER&gt;_genotype</code>, <code>lab_name</code>, <code>tested_at</code>, <code>notes</code>. {link}. Páruje se podle <code>sample_id</code> na vzorek a jeho psa. Hodnoty <code>X</code> / prázdné = bez výsledku. Neznámé markery se založí automaticky podle hlavičky.', [
            'link' => '<a href="/admin/genetics/import/template.csv">' . t('Stáhnout šablonu') . '</a>',
        ]) ?>
    </p>
    <form method="post" action="/admin/genetics/import" enctype="multipart/form-data">
        <?= \App\Core\Csrf::field() ?>
        <input type="file" name="file" accept=".csv,text/csv" required>
        <button type="submit" class="btn btn--primary"><?= t('Nahrát a zkontrolovat') ?></button>
    </form>
</div>

<?php if ($preview !== null): ?>
    <div class="card">
        <h2><?= t('Náhled') ?></h2>
        <p>
            <?= t('Řádků: {total}, spárováno (nalezen pes): {valid}, nenalezeno: {invalid}, markerů ve sloupcích: {markers}.', [
                'total' => '<strong>' . (int) $preview['summary']['total'] . '</strong>',
                'valid' => '<strong style="color:var(--ok)">' . (int) $preview['summary']['valid'] . '</strong>',
                'invalid' => '<strong style="color:var(--danger)">' . (int) $preview['summary']['invalid'] . '</strong>',
                'markers' => '<strong>' . (int) $preview['summary']['markers'] . '</strong>',
            ]) ?>
        </p>

        <?php if ($preview['summary']['valid'] > 0): ?>
            <form method="post" action="/admin/genetics/import/commit" onsubmit="return confirm(<?= e(json_encode(t('Naimportovat genotypy pro {count} spárovaných řádků?', ['count' => (int) $preview['summary']['valid']]), JSON_UNESCAPED_UNICODE)) ?>);">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn btn--primary"><?= t('Potvrdit import') ?></button>
                <span class="muted"><?= t('Nespárované řádky (neznámý sample_id) se přeskočí.') ?></span>
            </form>
        <?php else: ?>
            <div class="alert alert--error"><?= t('Žádný řádek se nepodařilo spárovat. Zkontrolujte, že sample_id odpovídá vzorkům v systému.') ?></div>
        <?php endif; ?>

        <table class="table" style="margin-top:1rem">
            <thead><tr><th><?= t('Řádek') ?></th><th>sample_id</th><th><?= t('Pes') ?></th><th><?= t('Hodnot') ?></th></tr></thead>
            <tbody>
            <?php foreach (array_slice($preview['rows'], 0, 1000) as $r): ?>
                <tr>
                    <td><?= (int) $r['line'] ?></td>
                    <td><code><?= e($r['sample_id']) ?></code></td>
                    <td><?= $r['found'] ? '<span style="color:var(--ok)">#' . (int) $r['dog_id'] . '</span>' : '<span style="color:var(--danger)">' . t('nenalezen') . '</span>' ?></td>
                    <td><?= (int) $r['values'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
