<?php
/** @var array<int, array<string, mixed>> $dogs */
/** @var \App\Support\Paginator $pager */
/** @var array<string, mixed> $filters */
/** @var string $sort */
/** @var string $dir */
/** @var int|null $currentBreedId */
/** @var string|null $notice */

$qs = static function (array $over) use ($filters, $sort, $dir): string {
    $base = [
        'q' => $filters['q'], 'code' => $filters['code'], 'status' => $filters['status'],
        'sort' => $sort, 'dir' => $dir,
    ];
    $merged = array_filter(array_merge($base, $over), static fn ($v): bool => $v !== '' && $v !== null);
    return '/admin/dogs?' . http_build_query($merged);
};
?>
<?php
$exportUrl = '/admin/dogs/export.csv';
$exportParams = array_filter([
    'q' => $filters['q'], 'code' => $filters['code'], 'status' => $filters['status'],
    'sort' => $sort, 'dir' => $dir,
], static fn ($v): bool => $v !== '' && $v !== null);
if ($exportParams !== []) {
    $exportUrl .= '?' . http_build_query($exportParams);
}
?>
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Psi</h1>
    <span>
        <a class="btn" href="/admin/import">Import CSV</a>
        <a class="btn" href="<?= e($exportUrl) ?>">Export CSV</a>
        <a class="btn btn--primary" href="/admin/dogs/new">+ Novy pes</a>
    </span>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if ($currentBreedId === null): ?>
    <p class="muted">Zobrazuji vsechna plemena. Pro filtr podle plemene pouzijte prepinac nahore.</p>
<?php endif; ?>

<form method="get" action="/admin/dogs" class="card filters">
    <input type="text" name="q" value="<?= e($filters['q']) ?>" placeholder="Jmeno psa">
    <input type="text" name="code" value="<?= e($filters['code']) ?>" placeholder="Cip / cislo prukazu">
    <select name="status">
        <option value="">Stav: vse</option>
        <option value="alive"<?= $filters['status'] === 'alive' ? ' selected' : '' ?>>Zivy</option>
        <option value="dead"<?= $filters['status'] === 'dead' ? ' selected' : '' ?>>Uhynuly</option>
    </select>
    <select name="sort">
        <?php foreach (['name' => 'Jmeno', 'breed' => 'Plemeno', 'birth' => 'Narozeni', 'updated' => 'Aktualizace'] as $k => $lbl): ?>
            <option value="<?= $k ?>"<?= $sort === $k ? ' selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
    </select>
    <select name="dir">
        <option value="asc"<?= $dir === 'asc' ? ' selected' : '' ?>>vzestupne</option>
        <option value="desc"<?= $dir === 'desc' ? ' selected' : '' ?>>sestupne</option>
    </select>
    <button class="btn" type="submit">Filtrovat</button>
</form>

<div class="card">
    <?php if ($dogs === []): ?>
        <p class="muted">Zadni psi neodpovidaji filtru.</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr><th>Jmeno</th><th>Plemeno</th><th>Pohlavi</th><th>Narozeni</th><th>Majitel</th><th>Stav</th></tr>
            </thead>
            <tbody>
            <?php foreach ($dogs as $d): ?>
                <tr>
                    <td><a href="/admin/dogs/<?= (int) $d['id'] ?>"><?= e($d['name']) ?></a>
                        <?php if (!empty($d['chip_number'])): ?><br><span class="muted"><?= e($d['chip_number']) ?></span><?php endif; ?>
                    </td>
                    <td><?= e($d['breed_name']) ?></td>
                    <td><?= e($d['sex']) ?></td>
                    <td><?= e($d['birth_date'] ?? '') ?></td>
                    <td>
                        <?php if (!empty($d['owner_id'])): ?>
                            <a href="/admin/owners/<?= (int) $d['owner_id'] ?>"><?= e($d['owner_name']) ?></a>
                        <?php else: ?><span class="muted">-</span><?php endif; ?>
                    </td>
                    <td><?= empty($d['death_date']) ? 'zivy' : 'uhynuly' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="pager">
            <span class="muted"><?= $pager->from() ?>-<?= $pager->to() ?> z <?= $pager->total ?></span>
            <span>
                <?php if ($pager->hasPrev()): ?><a class="btn" href="<?= e($qs(['page' => $pager->page - 1])) ?>">&larr; Predchozi</a><?php endif; ?>
                <?php if ($pager->hasNext()): ?><a class="btn" href="<?= e($qs(['page' => $pager->page + 1])) ?>">Dalsi &rarr;</a><?php endif; ?>
            </span>
        </div>
    <?php endif; ?>
</div>
