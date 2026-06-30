<?php
/** @var array<int, array<string, mixed>> $rows */
/** @var \App\Support\Paginator $pager */
/** @var array<string, mixed> $filters */
/** @var string $sort */
/** @var string $dir */
/** @var array<int, array<string, mixed>> $markers */
/** @var string|null $notice */
/** @var string|null $error */

$qs = static function (array $over) use ($filters, $sort, $dir): string {
    $base = ['q' => $filters['q'], 'marker_id' => $filters['marker_id'], 'genotype' => $filters['genotype'], 'sort' => $sort, 'dir' => $dir];
    $merged = array_filter(array_merge($base, $over), static fn ($v): bool => $v !== '' && $v !== null);
    return '/admin/genetics?' . http_build_query($merged);
};
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
<p class="muted">PCR / genetické výsledky jsou viditelné jen výzkumnému týmu a klubům, nikdy majitelům.</p>

<form method="get" action="/admin/genetics" class="card filters">
    <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Jméno psa">
    <select name="marker_id">
        <option value="">Marker: vše</option>
        <?php foreach ($markers as $m): ?>
            <option value="<?= (int) $m['id'] ?>"<?= (int) $filters['marker_id'] === (int) $m['id'] ? ' selected' : '' ?>><?= e($m['marker_code']) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="text" name="genotype" value="<?= e($filters['genotype']) ?>" placeholder="Genotyp (napr. GG)">
    <select name="sort">
        <?php foreach (['dog' => 'Pes', 'marker' => 'Marker', 'genotype' => 'Genotyp', 'tested' => 'Datum testu'] as $k => $lbl): ?>
            <option value="<?= $k ?>"<?= $sort === $k ? ' selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
    </select>
    <select name="dir">
        <option value="asc"<?= $dir === 'asc' ? ' selected' : '' ?>>vzestupně</option>
        <option value="desc"<?= $dir === 'desc' ? ' selected' : '' ?>>sestupně</option>
    </select>
    <button class="btn" type="submit">Filtrovat</button>
</form>

<div class="card">
    <?php if ($rows === []): ?>
        <p class="muted">Žádné genotypy.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Pes</th><th>Plemeno</th><th>Gen / marker</th><th>Genotyp</th><th>Datum testu</th><th>Stav</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><a href="/admin/dogs/<?= (int) $r['dog_id'] ?>"><?= e($r['dog_name']) ?></a></td>
                    <td><?= e($r['breed_name'] ?? '') ?></td>
                    <td><?= e($r['gene_symbol']) ?> / <code><?= e($r['marker_code']) ?></code></td>
                    <td><strong><?= e($r['genotype']) ?></strong></td>
                    <td><?= e(\App\Support\Dates::toCz($r['tested_at'] ?? null)) ?></td>
                    <td><?= e($r['validation_status']) ?></td>
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
    <?php endif; ?>
</div>

<div class="card">
    <h2>Ruční zadání genotypu</h2>
    <form method="post" action="/admin/genetics/manual" class="form-row">
        <?= \App\Core\Csrf::field() ?>
        <div><label for="dog_id">ID psa</label><input type="number" id="dog_id" name="dog_id" required></div>
        <div>
            <label for="marker_id">Marker</label>
            <select id="marker_id" name="marker_id" required>
                <option value="">- vyberte -</option>
                <?php foreach ($markers as $m): ?>
                    <option value="<?= (int) $m['id'] ?>"><?= e($m['marker_code']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><label for="genotype">Genotyp</label><input type="text" id="genotype" name="genotype" placeholder="GG" required></div>
        <div class="form-row__action"><button type="submit" class="btn btn--primary">Uložit</button></div>
    </form>
    <p class="muted">ID psa najdete v sekci Psi (v URL detailu). Marker musíte mít založený (nebo vznikne při CSV importu).</p>
</div>
