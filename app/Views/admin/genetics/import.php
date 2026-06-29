<?php
/** @var array{rows:array<int,array<string,mixed>>, summary:array<string,int>, markerColumns:array<int,array{column:string,code:string}>}|null $preview */
/** @var string|null $error */
?>
<div class="page-head">
    <h1>Import genotypu (PCR CSV)</h1>
    <p><a href="/admin/genetics">&larr; Zpet na genotypy</a></p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Nahrat CSV</h2>
    <p class="muted">
        Siroky format: <code>sample_id</code>, <code>expected_phenotype</code>, sloupce
        <code>&lt;MARKER&gt;_genotype</code>, <code>lab_name</code>, <code>tested_at</code>, <code>notes</code>.
        <a href="/admin/genetics/import/template.csv">Stahnout sablonu</a>.
        Paruje se podle <code>sample_id</code> na vzorek a jeho psa. Hodnoty <code>X</code> / prazdne = bez vysledku.
        Nezname markery se zaloji automaticky podle hlavicky.
    </p>
    <form method="post" action="/admin/genetics/import" enctype="multipart/form-data">
        <?= \App\Core\Csrf::field() ?>
        <input type="file" name="file" accept=".csv,text/csv" required>
        <button type="submit" class="btn btn--primary">Nahrat a zkontrolovat</button>
    </form>
</div>

<?php if ($preview !== null): ?>
    <div class="card">
        <h2>Nahled</h2>
        <p>
            Radku: <strong><?= (int) $preview['summary']['total'] ?></strong>,
            spárováno (nalezen pes): <strong style="color:var(--ok)"><?= (int) $preview['summary']['valid'] ?></strong>,
            nenalezeno: <strong style="color:var(--danger)"><?= (int) $preview['summary']['invalid'] ?></strong>,
            markeru ve sloupcich: <strong><?= (int) $preview['summary']['markers'] ?></strong>.
        </p>

        <?php if ($preview['summary']['valid'] > 0): ?>
            <form method="post" action="/admin/genetics/import/commit" onsubmit="return confirm('Naimportovat genotypy pro <?= (int) $preview['summary']['valid'] ?> sparovanych radku?');">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn btn--primary">Potvrdit import</button>
                <span class="muted">Nesparovane radky (neznamy sample_id) se preskoci.</span>
            </form>
        <?php else: ?>
            <div class="alert alert--error">Zadny radek se nepodarilo sparovat. Zkontrolujte, ze sample_id odpovida vzorkum v systemu.</div>
        <?php endif; ?>

        <table class="table" style="margin-top:1rem">
            <thead><tr><th>Radek</th><th>sample_id</th><th>Pes</th><th>Hodnot</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($preview['rows'], 0, 1000) as $r): ?>
                <tr>
                    <td><?= (int) $r['line'] ?></td>
                    <td><code><?= e($r['sample_id']) ?></code></td>
                    <td><?= $r['found'] ? '<span style="color:var(--ok)">#' . (int) $r['dog_id'] . '</span>' : '<span style="color:var(--danger)">nenalezen</span>' ?></td>
                    <td><?= (int) $r['values'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
