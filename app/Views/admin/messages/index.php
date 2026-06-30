<?php
/** @var array<int, array<string, mixed>> $threads */
/** @var string $status */
/** @var array<int, string> $statuses */
/** @var string|null $notice */
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

<div class="card">
    <?php if ($threads === []): ?>
        <p class="muted">Žádná vlákna.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Pes</th><th>Stav</th><th>Zpráv</th><th>Poslední</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($threads as $t): ?>
                <tr>
                    <td><?= e($t['dog_name'] ?? ('#' . (int) $t['entity_id'])) ?></td>
                    <td><?= e($t['status']) ?></td>
                    <td><?= (int) $t['msg_count'] ?></td>
                    <td><?= e(\App\Support\Dates::toCz(substr((string) $t['last_message_at'], 0, 10))) ?></td>
                    <td><a href="/admin/messages/<?= (int) $t['id'] ?>">Otevřít</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
