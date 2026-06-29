<?php
/** @var array<int, array<string, mixed>> $owners */
/** @var \App\Support\Paginator $pager */
/** @var string $search */
/** @var string|null $notice */

$qs = static function (array $over) use ($search): string {
    $merged = array_filter(array_merge(['q' => $search], $over), static fn ($v): bool => $v !== '' && $v !== null);
    return '/admin/owners?' . http_build_query($merged);
};
?>
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Majitele</h1>
    <a class="btn btn--primary" href="/admin/owners/new">+ Novy majitel</a>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>

<form method="get" action="/admin/owners" class="card filters">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Jmeno majitele">
    <button class="btn" type="submit">Hledat</button>
</form>

<div class="card">
    <?php if ($owners === []): ?>
        <p class="muted">Zadni majitele.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Jmeno</th><th>Primarni e-mail</th><th>Aktualni psi</th></tr></thead>
            <tbody>
            <?php foreach ($owners as $o): ?>
                <tr>
                    <td><a href="/admin/owners/<?= (int) $o['id'] ?>"><?= e($o['display_name']) ?></a></td>
                    <td><?= e($o['primary_email'] ?? '') ?: '<span class="muted">-</span>' ?></td>
                    <td><?= (int) $o['dog_count'] ?></td>
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
