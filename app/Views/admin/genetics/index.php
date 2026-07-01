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
                    <td class="col-name"><a href="/admin/genetics/<?= (int) $d['id'] ?>"><?= e($d['name']) ?></a></td>
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
    <?php if ($genePanel === []): ?>
        <p class="muted">Nejdříve založte geny v sekci <a href="/admin/genetics/markers">Geny a markery</a>.</p>
    <?php else: ?>
        <form method="post" action="/admin/genetics/manual">
            <?= \App\Core\Csrf::field() ?>
            <label for="geno-dog-search">Pes</label>
            <div class="ac" id="geno-ac-wrap">
                <input type="text" id="geno-dog-search" autocomplete="off" placeholder="Napište jméno psa a vyberte z nabídky">
                <input type="hidden" id="geno-dog-id" name="dog_id" value="">
                <div class="ac-list" id="geno-ac" hidden></div>
            </div>

            <p class="muted" style="margin-top:1rem; margin-bottom:0.3rem;">Genotypy (vyplňte jen ty, které znáte):</p>
            <div class="form-row">
                <?php foreach ($genePanel as $g): ?>
                    <div>
                        <label for="g-<?= (int) $g['marker_id'] ?>"><?= e($g['symbol']) ?></label>
                        <input type="text" id="g-<?= (int) $g['marker_id'] ?>" name="g[<?= (int) $g['marker_id'] ?>]" placeholder="GG" style="max-width:100px;">
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn--primary">Uložit genotypy</button>
        </form>
        <p class="muted">Editaci celé genetiky psa najdete po kliknutí na psa v tabulce nahoře.</p>
    <?php endif; ?>
</div>

<script src="<?= e(asset('assets/datatable.js')) ?>"></script>
<script>
(function () {
    var input = document.getElementById('geno-dog-search');
    var hidden = document.getElementById('geno-dog-id');
    var list = document.getElementById('geno-ac');
    if (!input || !hidden || !list) { return; }
    var timer;

    function close() { list.hidden = true; list.innerHTML = ''; }

    input.addEventListener('input', function () {
        hidden.value = ''; // vyber je platny jen po kliknuti z nabidky
        clearTimeout(timer);
        var q = input.value.trim();
        if (q.length < 2) { close(); return; }
        timer = setTimeout(function () {
            fetch('/admin/genetics/dog-suggest?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (items) {
                    list.innerHTML = '';
                    if (!items.length) { close(); return; }
                    items.forEach(function (it) {
                        var d = document.createElement('button');
                        d.type = 'button';
                        d.className = 'ac-item';
                        d.innerHTML = '<strong></strong> <span class="muted"></span>';
                        d.querySelector('strong').textContent = it.name;
                        d.querySelector('.muted').textContent = it.breed_name || '';
                        d.addEventListener('click', function () {
                            input.value = it.name;
                            hidden.value = it.id;
                            close();
                        });
                        list.appendChild(d);
                    });
                    list.hidden = false;
                }).catch(close);
        }, 200);
    });

    document.addEventListener('click', function (e) {
        if (!document.getElementById('geno-ac-wrap').contains(e.target)) { close(); }
    });
})();
</script>
