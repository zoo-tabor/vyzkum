<?php
/** @var array<int, array<string, mixed>> $dogs */
/** @var \App\Support\Paginator $pager */
/** @var array<string, mixed> $filters */
/** @var int|null $currentBreedId */
/** @var array<int, array{id:int, marker_code:string}> $markers */
/** @var array<int, array<int, array<string, mixed>>> $samplesByDog */
/** @var array<int, array<int, string>> $genotypesByDog */
/** @var string|null $notice */

use App\Support\Age;
use App\Support\Countries;
use App\Support\Dates;

$qs = static function (array $over) use ($filters): string {
    $base = ['q' => $filters['q'], 'kennel' => $filters['kennel'], 'status' => $filters['status']];
    $merged = array_filter(array_merge($base, $over), static fn ($v): bool => $v !== '' && $v !== null);
    return '/admin/dogs?' . http_build_query($merged);
};
$exportUrl = '/admin/dogs/export.csv';
$exportParams = array_filter(['q' => $filters['q'], 'kennel' => $filters['kennel'], 'status' => $filters['status']], static fn ($v): bool => $v !== '');
if ($exportParams !== []) {
    $exportUrl .= '?' . http_build_query($exportParams);
}
?>
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Psi</h1>
    <span>
        <a class="btn" href="/admin/import">Import CSV</a>
        <a class="btn" href="<?= e($exportUrl) ?>">Export CSV</a>
        <a class="btn btn--primary" href="/admin/dogs/new">+ Nový pes</a>
    </span>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if ($currentBreedId === null): ?>
    <p class="muted">Zobrazuji všechna plemena. Pro sloupce genotypů vyberte konkrétní plemeno v přepínači nahoře.</p>
<?php endif; ?>

<form method="get" action="/admin/dogs" class="card filters">
    <input type="text" id="f-q" name="q" value="<?= e($filters['q']) ?>" placeholder="Jméno psa" list="dl-names" autocomplete="off">
    <datalist id="dl-names"></datalist>
    <input type="text" id="f-kennel" name="kennel" value="<?= e($filters['kennel']) ?>" placeholder="Chovatelská stanice" list="dl-kennels" autocomplete="off">
    <datalist id="dl-kennels"></datalist>
    <select name="status">
        <option value="">Stav: vše</option>
        <option value="alive"<?= $filters['status'] === 'alive' ? ' selected' : '' ?>>Živý</option>
        <option value="dead"<?= $filters['status'] === 'dead' ? ' selected' : '' ?>>Uhynulý</option>
    </select>
    <button class="btn" type="submit">Filtrovat</button>
</form>

<div class="card">
    <?php if ($dogs === []): ?>
        <p class="muted">Žádní psi neodpovídají filtru.</p>
    <?php else: ?>
        <table class="table table--dogs">
            <thead>
            <tr>
                <th class="sortable" data-type="text">Jméno</th>
                <th class="sortable" data-type="text">Plemeno</th>
                <th>Pohlaví</th>
                <th class="sortable" data-type="num">Věk</th>
                <th class="sortable" data-type="text">Země</th>
                <th>Vzorky</th>
                <th class="sortable" data-type="text">DNA izol.</th>
                <?php foreach ($markers as $m): ?>
                    <th class="sortable" data-type="text"><?= e($m['marker_code']) ?></th>
                <?php endforeach; ?>
                <th class="sortable" data-type="text">GWAS</th>
                <th class="sortable" data-type="text">Majitel</th>
                <th class="sortable" data-type="text">Stav</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($dogs as $d): ?>
                <?php
                $ref = Age::referenceDate($d['death_date'] ?? null, $d['alive_confirmed_at'] ?? null, $d['newest_sample_received'] ?? null);
                $age = Age::years($d['birth_date'] ?? null, $ref);
                $dogSamples = $samplesByDog[(int) $d['id']] ?? [];
                $dogGenos = $genotypesByDog[(int) $d['id']] ?? [];
                ?>
                <tr>
                    <td class="col-name"><a href="/admin/dogs/<?= (int) $d['id'] ?>"><?= e($d['name']) ?></a>
                        <?php if (!empty($d['chip_number'])): ?><br><span class="muted"><?= e($d['chip_number']) ?></span><?php endif; ?>
                    </td>
                    <td><?= e($d['breed_name']) ?></td>
                    <td><?= e($d['sex']) ?></td>
                    <td data-sort="<?= $age ?? -1 ?>"><?= $age !== null ? (int) $age : '-' ?></td>
                    <td title="<?= e(Countries::name($d['country'] ?? null) ?? '') ?>"><?= e($d['country'] ?? '') ?: '-' ?></td>
                    <td>
                        <?php if ($dogSamples === []): ?><span class="muted">-</span><?php else: ?>
                            <?php foreach ($dogSamples as $s): ?>
                                <div><code><?= e($s['sample_id']) ?></code>
                                    <?php if (!empty($s['received_at'])): ?><span class="muted">(<?= e(Dates::toCz(substr((string) $s['received_at'], 0, 10))) ?>)</span><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= e(Dates::toCz($d['dna_isolated_at'] ?? null)) ?: '-' ?></td>
                    <?php foreach ($markers as $m): ?>
                        <td><?= e($dogGenos[$m['id']] ?? '') ?: '-' ?></td>
                    <?php endforeach; ?>
                    <td><?= e($d['gwas_status'] ?? '') ?: '-' ?></td>
                    <td>
                        <?php if (!empty($d['owner_id'])): ?>
                            <a href="/admin/owners/<?= (int) $d['owner_id'] ?>"><?= e($d['owner_name']) ?></a>
                        <?php else: ?><span class="muted">-</span><?php endif; ?>
                    </td>
                    <td><?= empty($d['death_date']) ? 'živý' : 'uhynulý' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pager">
            <span class="muted"><?= $pager->from() ?>-<?= $pager->to() ?> z <?= $pager->total ?></span>
            <span>
                <?php if ($pager->hasPrev()): ?><a class="btn" href="<?= e($qs(['page' => $pager->page - 1])) ?>">&larr; Předchozí</a><?php endif; ?>
                <?php if ($pager->hasNext()): ?><a class="btn" href="<?= e($qs(['page' => $pager->page + 1])) ?>">Další &rarr;</a><?php endif; ?>
            </span>
        </div>
        <p class="muted">Řazení: klikněte na záhlaví sloupce (A→Z / Z→A). Řadí se aktuálně zobrazená stránka.</p>
    <?php endif; ?>
</div>

<script>
(function () {
    // Razeni pres hlavicku tabulky
    document.querySelectorAll('table.table--dogs th.sortable').forEach(function (th) {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function () {
            var table = th.closest('table');
            var tbody = table.querySelector('tbody');
            var idx = Array.prototype.indexOf.call(th.parentNode.children, th);
            var type = th.getAttribute('data-type') || 'text';
            var asc = th.getAttribute('data-dir') !== 'asc';
            th.parentNode.querySelectorAll('th').forEach(function (o) { o.removeAttribute('data-dir'); });
            th.setAttribute('data-dir', asc ? 'asc' : 'desc');
            var val = function (tr) {
                var td = tr.children[idx];
                if (!td) return '';
                return td.getAttribute('data-sort') !== null && td.getAttribute('data-sort') !== undefined ? (td.getAttribute('data-sort') || '') : td.textContent.trim();
            };
            Array.prototype.slice.call(tbody.querySelectorAll('tr')).sort(function (a, b) {
                var av = val(a), bv = val(b);
                if (type === 'num') { av = parseFloat(av); bv = parseFloat(bv); if (isNaN(av)) av = -Infinity; if (isNaN(bv)) bv = -Infinity; return asc ? av - bv : bv - av; }
                return asc ? String(av).localeCompare(String(bv), 'cs') : String(bv).localeCompare(String(av), 'cs');
            }).forEach(function (r) { tbody.appendChild(r); });
        });
    });

    // Naseptavac
    function wire(inputId, listId, field) {
        var input = document.getElementById(inputId), list = document.getElementById(listId);
        if (!input || !list) return;
        var t;
        input.addEventListener('input', function () {
            clearTimeout(t);
            var q = input.value.trim();
            if (q.length < 2) return;
            t = setTimeout(function () {
                fetch('/admin/dogs/suggest?field=' + field + '&q=' + encodeURIComponent(q))
                    .then(function (r) { return r.json(); })
                    .then(function (items) {
                        list.innerHTML = '';
                        items.forEach(function (v) { var o = document.createElement('option'); o.value = v; list.appendChild(o); });
                    }).catch(function () {});
            }, 200);
        });
    }
    wire('f-q', 'dl-names', 'name');
    wire('f-kennel', 'dl-kennels', 'kennel');
})();
</script>
