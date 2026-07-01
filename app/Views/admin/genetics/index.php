<?php
/** @var array<int, array{id:int, symbol:string}> $genes */
/** @var array<int, array<string, mixed>> $dogs */
/** @var array<int, array<int, string>> $genotypes */
/** @var int|null $currentBreedId */
/** @var array<int, array<string, mixed>> $markers */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Genetika</h1>
    <span>
        <a class="btn" href="/admin/genetics/markers">Geny a markery</a>
        <a class="btn" href="/admin/genetics/import">Import CSV</a>
        <a class="btn" href="/admin/genetics/export.csv">Export CSV</a>
    </span>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>
<p class="muted">Genetické výsledky vidí jen výzkumný tým a kluby, nikdy majitelé.
    <?php if ($currentBreedId === null): ?>Zobrazuji všechna plemena – pro přehlednější sloupce genů vyberte konkrétní plemeno nahoře.<?php endif; ?>
</p>

<div class="card">
    <?php if ($dogs === []): ?>
        <p class="muted">Zatím žádné genotypy.</p>
    <?php else: ?>
        <table class="table table--dogs" data-datatable data-per-page="25" data-per-page-options="25,50,100,all">
            <thead>
            <tr>
                <th>Pes</th>
                <th>Plemeno</th>
                <?php foreach ($genes as $g): ?>
                    <th><?= e($g['symbol']) ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($dogs as $d): ?>
                <?php $dogGenos = $genotypes[(int) $d['id']] ?? []; ?>
                <tr>
                    <td class="col-name"><a href="/admin/dogs/<?= (int) $d['id'] ?>"><?= e($d['name']) ?></a></td>
                    <td><?= e($d['breed_name'] ?? '') ?></td>
                    <?php foreach ($genes as $g): ?>
                        <td><?= e($dogGenos[$g['id']] ?? '') ?: '-' ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="muted">Řazení: šipky ↑/↓ v záhlaví. Filtr sloupce (např. genotyp): ikona ⌕. Kliknutím na psa zobrazíte detail.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Ruční zadání genotypu</h2>
    <form method="post" action="/admin/genetics/manual" class="form-row">
        <?= \App\Core\Csrf::field() ?>
        <div><label for="dog_id">ID psa</label><input type="number" id="dog_id" name="dog_id" required></div>
        <div>
            <label for="marker_id">Gen / marker</label>
            <select id="marker_id" name="marker_id" required>
                <option value="">- vyberte -</option>
                <?php foreach ($markers as $m): ?>
                    <option value="<?= (int) $m['id'] ?>"><?= e($m['gene_symbol'] ?? $m['marker_code']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><label for="genotype">Genotyp</label><input type="text" id="genotype" name="genotype" placeholder="GG" required></div>
        <div class="form-row__action"><button type="submit" class="btn btn--primary">Uložit</button></div>
    </form>
    <p class="muted">ID psa najdete v sekci Psi (v URL detailu). Editaci celé genetiky psa najdete po kliknutí na psa v tabulce.</p>
</div>

<script src="<?= e(asset('assets/datatable.js')) ?>"></script>
