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
    <h1>Majitelé</h1>
    <a class="btn btn--primary" href="/admin/owners/new">+ Nový majitel</a>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>

<form method="get" action="/admin/owners" class="card filters">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Jméno majitele">
    <button class="btn" type="submit">Hledat</button>
</form>

<div class="card">
    <?php if ($owners === []): ?>
        <p class="muted">Žádní majitelé.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Jméno</th><th>Primární e-mail</th><th>Tel. číslo</th><th>Psi</th><th>Poslední aktualizace</th><th>Poznámka</th></tr></thead>
            <tbody>
            <?php foreach ($owners as $o): ?>
                <?php
                $lastAct = (string) ($o['last_activity'] ?? '');
                $lastActShow = ($lastAct !== '' && substr($lastAct, 0, 4) !== '1000') ? \App\Support\Dates::toCz(substr($lastAct, 0, 10)) : '-';
                $note = trim((string) ($o['note'] ?? ''));
                ?>
                <tr>
                    <td><a href="/admin/owners/<?= (int) $o['id'] ?>"><?= e($o['display_name']) ?></a></td>
                    <td><?= e($o['primary_email'] ?? '') ?: '<span class="muted">-</span>' ?></td>
                    <td><?= e(\App\Support\Phone::formatCz($o['primary_phone'] ?? null)) ?: '<span class="muted">-</span>' ?></td>
                    <td><?= (int) $o['dog_count'] ?></td>
                    <td><?= e($lastActShow) ?></td>
                    <td><?= $note !== '' ? e(mb_strimwidth($note, 0, 40, '…')) : '<span class="muted">-</span>' ?></td>
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
