<?php
/** @var array{rows:array<int,array{line:int,data:array<string,string>,errors:array<int,string>}>, summary:array{total:int,valid:int,invalid:int}}|null $preview */
/** @var string|null $error */
/** @var string|null $name */
?>
<div class="page-head"><h1>Import psu a majitelu (CSV)</h1></div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Nahrat CSV</h2>
    <p class="muted">
        Sloupce dle sablony (poradi nerozhoduje, hlavicka podle nazvu).
        <a href="/admin/import/template.csv">Stahnout prazdnou sablonu</a>.
        Plemena musi existovat predem (zalozte je v sekci Plemena). Vice e-mailu/telefonu
        oddelte strednikem. Datumy ve formatu YYYY-MM-DD. Sloupce sample_* se zatim
        neimportuji (modul vzorku prijde pozdeji), sample_received_at se ulozi u psa.
    </p>
    <form method="post" action="/admin/import" enctype="multipart/form-data">
        <?= \App\Core\Csrf::field() ?>
        <input type="file" name="file" accept=".csv,text/csv" required>
        <button type="submit" class="btn btn--primary">Nahrat a zkontrolovat</button>
    </form>
</div>

<?php if ($preview !== null): ?>
    <div class="card">
        <h2>Nahled importu<?= !empty($name) ? ' - ' . e($name) : '' ?></h2>
        <p>
            Celkem radku: <strong><?= (int) $preview['summary']['total'] ?></strong>,
            v poradku: <strong style="color:var(--ok)"><?= (int) $preview['summary']['valid'] ?></strong>,
            s chybou: <strong style="color:var(--danger)"><?= (int) $preview['summary']['invalid'] ?></strong>.
        </p>

        <?php if ($preview['summary']['valid'] > 0): ?>
            <form method="post" action="/admin/import/commit" onsubmit="return confirm('Naimportovat <?= (int) $preview['summary']['valid'] ?> platnych radku?');">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn btn--primary">Potvrdit import (<?= (int) $preview['summary']['valid'] ?> radku)</button>
                <span class="muted">Radky s chybou se preskoci.</span>
            </form>
        <?php else: ?>
            <div class="alert alert--error">Zadny platny radek k importu. Opravte chyby a nahrajte znovu.</div>
        <?php endif; ?>

        <table class="table" style="margin-top:1rem">
            <thead><tr><th>Radek</th><th>Plemeno</th><th>Pes</th><th>Majitel</th><th>Stav</th></tr></thead>
            <tbody>
            <?php foreach (array_slice($preview['rows'], 0, 1000) as $row): ?>
                <tr>
                    <td><?= (int) $row['line'] ?></td>
                    <td><?= e($row['data']['breed_slug'] ?? '') ?></td>
                    <td><?= e($row['data']['dog_name'] ?? '') ?></td>
                    <td><?= e($row['data']['owner_name'] ?? '') ?></td>
                    <td>
                        <?php if ($row['errors'] === []): ?>
                            <span style="color:var(--ok)">OK</span>
                        <?php else: ?>
                            <span style="color:var(--danger)"><?= e(implode('; ', $row['errors'])) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (count($preview['rows']) > 1000): ?>
            <p class="muted">Zobrazeno prvnich 1000 radku z <?= count($preview['rows']) ?>.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>
