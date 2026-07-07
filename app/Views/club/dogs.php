<?php
/** @var array<int, array<string, mixed>> $breeds */
/** @var int $selected */
/** @var array<int, array<string, mixed>> $dogs */
/** @var \App\Support\Paginator $pager */
?>
<div class="page-head"><h1><?= t('Psi') ?></h1></div>

<?php if ($breeds === []): ?>
    <div class="card"><p><?= t('Váš účet zatím nemá přiřazené žádné plemeno.') ?></p></div>
<?php else: ?>
    <form method="get" action="/club/dogs" class="card filters">
        <label for="breed"><?= t('Plemeno:') ?></label>
        <select id="breed" name="breed" onchange="this.form.submit()">
            <?php foreach ($breeds as $b): ?>
                <option value="<?= (int) $b['id'] ?>"<?= $selected === (int) $b['id'] ? ' selected' : '' ?>><?= e(\App\Support\Breeds::translate($b['name'])) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="card">
        <?php if ($dogs === []): ?>
            <p class="muted"><?= t('Žádní psi.') ?></p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th><?= t('Jméno') ?></th><th><?= t('Pohlaví') ?></th><th><?= t('Narození') ?></th><th><?= t('Stav') ?></th><th><?= t('Majitel') ?></th></tr></thead>
                <tbody>
                <?php foreach ($dogs as $d): ?>
                    <tr>
                        <td><?= e($d['name']) ?></td>
                        <td><?= e($d['sex']) ?></td>
                        <td><?= e(\App\Support\Dates::toCz($d['birth_date'] ?? null)) ?></td>
                        <td><?= empty($d['death_date']) ? t('živý') : t('uhynulý') ?></td>
                        <td><?= e($d['owner_name'] ?? '') ?: '<span class="muted">-</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="muted"><?= t('Kontaktní údaje majitelů nejsou klubům zpřístupněny.') ?></p>
            <div class="pager">
                <span class="muted"><?= t('{from}-{to} z {total}', ['from' => $pager->from(), 'to' => $pager->to(), 'total' => $pager->total]) ?></span>
                <span>
                    <?php if ($pager->hasPrev()): ?><a class="btn" href="/club/dogs?breed=<?= $selected ?>&page=<?= $pager->page - 1 ?>">&larr; <?= t('Předchozí') ?></a><?php endif; ?>
                    <?php if ($pager->hasNext()): ?><a class="btn" href="/club/dogs?breed=<?= $selected ?>&page=<?= $pager->page + 1 ?>"><?= t('Další') ?> &rarr;</a><?php endif; ?>
                </span>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
