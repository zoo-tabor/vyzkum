<?php
/** @var array<int, array{owner_id: int|null, owner_name: string, count: int, unresolved: bool, last: ?string}> $owners */
/** @var string|null $notice */
?>
<div class="page-head"><h1>Zprávy</h1></div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>

<p class="muted">Tučně jsou majitelé s dosud nevyřešeným vláknem. Kliknutím zobrazíte jejich konverzace.</p>

<div class="card">
    <?php if ($owners === []): ?>
        <p class="muted">Žádné zprávy.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Majitel</th><th>Vláken</th><th>Poslední zpráva</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($owners as $o): ?>
                <?php $href = '/admin/messages/owner/' . (int) ($o['owner_id'] ?? 0); ?>
                <tr<?= $o['unresolved'] ? ' style="font-weight:600"' : '' ?>>
                    <td><a href="<?= e($href) ?>"><?= e($o['owner_name']) ?></a></td>
                    <td><?= (int) $o['count'] ?></td>
                    <td><?= e(\App\Support\Dates::toCzDateTime((string) ($o['last'] ?? ''))) ?></td>
                    <td><a href="<?= e($href) ?>">Otevřít &rarr;</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
