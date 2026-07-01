<?php
/** @var int $ownerId */
/** @var string $ownerName */
/** @var array<int, array<string, mixed>> $threads */
/** @var string|null $notice */

$unresolved = static fn (string $s): bool => !in_array($s, ['resolved', 'archived'], true);
?>
<div class="page-head">
    <h1><?= e($ownerName) ?></h1>
    <p>
        <a href="/admin/messages">&larr; Zpět na majitele</a>
        <?php if ($ownerId > 0): ?>
            &middot; <a href="/admin/owners/<?= $ownerId ?>">Detail majitele</a>
        <?php endif; ?>
    </p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>

<p class="muted">Tučně jsou dosud nevyřešená vlákna (stav není „resolved" / „archived").</p>

<div class="card">
    <?php if ($threads === []): ?>
        <p class="muted">Žádná vlákna.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Vlákno</th><th>Stav</th><th>Zpráv</th><th>Poslední zpráva</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($threads as $t): ?>
                <?php
                $label = !empty($t['dog_name']) ? $t['dog_name'] : 'Obecná';
                $bold = $unresolved((string) $t['status']);
                ?>
                <tr<?= $bold ? ' style="font-weight:600"' : '' ?>>
                    <td><a href="/admin/messages/<?= (int) $t['id'] ?>"><?= e($label) ?></a></td>
                    <td><?= e($t['status']) ?></td>
                    <td><?= (int) $t['msg_count'] ?></td>
                    <td><?= e(\App\Support\Dates::toCzDateTime((string) $t['last_message_at'])) ?></td>
                    <td><a href="/admin/messages/<?= (int) $t['id'] ?>">Otevřít &rarr;</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
