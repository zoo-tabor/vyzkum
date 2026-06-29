<?php
/** @var array<int, array<string, mixed>> $breeds */
/** @var int $selected */
/** @var array<int, array<string, mixed>> $dogs */
/** @var \App\Support\Paginator $pager */
?>
<div class="page-head"><h1>Psi</h1></div>

<?php if ($breeds === []): ?>
    <div class="card"><p>Vas ucet zatim nema prirazene zadne plemeno.</p></div>
<?php else: ?>
    <form method="get" action="/club/dogs" class="card filters">
        <label for="breed">Plemeno:</label>
        <select id="breed" name="breed" onchange="this.form.submit()">
            <?php foreach ($breeds as $b): ?>
                <option value="<?= (int) $b['id'] ?>"<?= $selected === (int) $b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="card">
        <?php if ($dogs === []): ?>
            <p class="muted">Zadni psi.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Jmeno</th><th>Pohlavi</th><th>Narozeni</th><th>Stav</th><th>Majitel</th></tr></thead>
                <tbody>
                <?php foreach ($dogs as $d): ?>
                    <tr>
                        <td><?= e($d['name']) ?></td>
                        <td><?= e($d['sex']) ?></td>
                        <td><?= e(\App\Support\Dates::toCz($d['birth_date'] ?? null)) ?></td>
                        <td><?= empty($d['death_date']) ? 'zivy' : 'uhynuly' ?></td>
                        <td><?= e($d['owner_name'] ?? '') ?: '<span class="muted">-</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="muted">Kontaktni udaje majitelu nejsou klubum zpristupneny.</p>
            <div class="pager">
                <span class="muted"><?= $pager->from() ?>-<?= $pager->to() ?> z <?= $pager->total ?></span>
                <span>
                    <?php if ($pager->hasPrev()): ?><a class="btn" href="/club/dogs?breed=<?= $selected ?>&page=<?= $pager->page - 1 ?>">&larr; Predchozi</a><?php endif; ?>
                    <?php if ($pager->hasNext()): ?><a class="btn" href="/club/dogs?breed=<?= $selected ?>&page=<?= $pager->page + 1 ?>">Dalsi &rarr;</a><?php endif; ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
