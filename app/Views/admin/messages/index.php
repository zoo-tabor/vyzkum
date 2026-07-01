<?php
/** @var array<int, array{owner_id: int|null, owner_name: string, threads: array<int, array<string, mixed>>}> $groups */
/** @var string $status */
/** @var array<int, string> $statuses */
/** @var string|null $notice */

$unresolved = static fn (string $s): bool => !in_array($s, ['resolved', 'archived'], true);
?>
<div class="page-head"><h1>Zprávy</h1></div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>

<form method="get" action="/admin/messages" class="card filters">
    <select name="status">
        <option value="">Stav: vše</option>
        <?php foreach ($statuses as $s): ?>
            <option value="<?= e($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Filtrovat</button>
</form>

<p class="muted">Tučně zvýrazněná jsou dosud nevyřešená vlákna (stav není „resolved" / „archived").</p>

<?php if ($groups === []): ?>
    <div class="card"><p class="muted">Žádná vlákna.</p></div>
<?php else: ?>
    <?php foreach ($groups as $g): ?>
        <div class="card">
            <h2 style="margin-bottom:0.5rem">
                <?php if ($g['owner_id'] !== null): ?>
                    <a href="/admin/owners/<?= (int) $g['owner_id'] ?>"><?= e($g['owner_name']) ?></a>
                <?php else: ?>
                    <?= e($g['owner_name']) ?>
                <?php endif; ?>
            </h2>
            <table class="table">
                <thead><tr><th>Vlákno</th><th>Stav</th><th>Zpráv</th><th>Poslední zpráva</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($g['threads'] as $t): ?>
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
        </div>
    <?php endforeach; ?>
<?php endif; ?>
