<?php
/** @var array<string, mixed> $thread */
/** @var array<int, array<string, mixed>> $messages */
/** @var array<int, string> $statuses */
/** @var string|null $notice */
?>
<?php
$threadLabel = !empty($thread['dog_name'])
    ? $thread['dog_name']
    : (!empty($thread['owner_name']) ? 'Obecná – ' . $thread['owner_name'] : '#' . (int) $thread['entity_id']);
?>
<div class="page-head">
    <h1>Vlákno - <?= e($threadLabel) ?></h1>
    <p>
        <a href="/admin/messages">&larr; Zpět</a>
        <?php if (($thread['entity_type'] ?? '') === 'dog'): ?>
            &middot; <a href="/admin/dogs/<?= (int) $thread['entity_id'] ?>">Detail psa</a>
        <?php elseif (($thread['entity_type'] ?? '') === 'owner'): ?>
            &middot; <a href="/admin/owners/<?= (int) $thread['entity_id'] ?>">Detail majitele</a>
        <?php endif; ?>
    </p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="/admin/messages/<?= (int) $thread['id'] ?>/status" class="filters">
        <?= \App\Core\Csrf::field() ?>
        <label>Stav:</label>
        <select name="status">
            <?php foreach ($statuses as $s): ?>
                <option value="<?= e($s) ?>"<?= $thread['status'] === $s ? ' selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn" type="submit">Uložit stav</button>
    </form>
</div>

<div class="card">
    <?php if ($messages === []): ?>
        <p class="muted">Zatím žádné zprávy.</p>
    <?php else: ?>
        <?php foreach ($messages as $m): ?>
            <div style="border-top:1px solid var(--line); padding:0.5rem 0;">
                <strong><?= ($m['sender_role'] ?? '') === 'owner' ? 'Majitel' : 'Výzkumný tým' ?></strong>
                <span class="muted"><?= e($m['sender_email'] ?? '') ?> &middot; <?= e(\App\Support\Dates::toCz(substr((string) $m['created_at'], 0, 10))) ?></span>
                <div><?= nl2br(e((string) $m['body'])) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="post" action="/admin/messages/<?= (int) $thread['id'] ?>/reply" style="margin-top:0.75rem">
        <?= \App\Core\Csrf::field() ?>
        <label for="body">Odpověď</label>
        <textarea id="body" name="body" rows="3" required></textarea>
        <button type="submit" class="btn btn--primary">Odeslat odpověď</button>
    </form>
</div>
