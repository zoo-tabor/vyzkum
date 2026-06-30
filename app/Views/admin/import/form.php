<?php
/** @var array{rows:array<int,array{line:int,data:array<string,string>,errors:array<int,string>}>, summary:array{total:int,valid:int,invalid:int}}|null $preview */
/** @var string|null $error */
/** @var string|null $name */
?>
<div class="page-head"><h1>Import psů a majitelů (CSV)</h1></div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Nahrát CSV</h2>
    <p class="muted">
        Sloupce dle šablony (pořadí nerozhoduje, hlavička podle názvu).
        <a href="/admin/import/template.csv">Stáhnout prázdnou šablonu</a>.
        Plemena musí existovat předem (založte je v sekci Plemena). Více e-mailů/telefonů
        oddělte středníkem. Datumy ve formátu YYYY-MM-DD. Sloupce sample_* se zatím
        neimportují (modul vzorků přijde později), sample_received_at se uloží u psa.
    </p>
    <form method="post" action="/admin/import" enctype="multipart/form-data">
        <?= \App\Core\Csrf::field() ?>
        <input type="file" name="file" accept=".csv,text/csv" required>
        <button type="submit" class="btn btn--primary">Nahrát a zkontrolovat</button>
    </form>
</div>

<?php if ($preview !== null): ?>
    <div class="card">
        <h2>Náhled importu<?= !empty($name) ? ' - ' . e($name) : '' ?></h2>
        <p>
            Celkem řádků: <strong><?= (int) $preview['summary']['total'] ?></strong>,
            v pořádku: <strong style="color:var(--ok)"><?= (int) $preview['summary']['valid'] ?></strong>,
            s chybou: <strong style="color:var(--danger)"><?= (int) $preview['summary']['invalid'] ?></strong>.
        </p>

        <?php if ($preview['summary']['valid'] > 0): ?>
            <form method="post" action="/admin/import/commit" onsubmit="return confirm('Naimportovat <?= (int) $preview['summary']['valid'] ?> platných řádků?');">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn btn--primary">Potvrdit import (<?= (int) $preview['summary']['valid'] ?> řádků)</button>
                <span class="muted">Řádky s chybou se přeskočí.</span>
            </form>
        <?php else: ?>
            <div class="alert alert--error">Žádný platný řádek k importu. Opravte chyby a nahrajte znovu.</div>
        <?php endif; ?>

        <table class="table" style="margin-top:1rem">
            <thead><tr><th>Řádek</th><th>Plemeno</th><th>Pes</th><th>Majitel</th><th>Stav</th></tr></thead>
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
            <p class="muted">Zobrazeno prvních 1000 řádků z <?= count($preview['rows']) ?>.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>
