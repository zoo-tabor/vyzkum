<?php
/** @var string $heading */
/** @var int $dogId */
/** @var array<int, array<string, mixed>> $messages */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head">
    <h1><?= e($heading) ?></h1>
    <p><a href="/portal/messages">&larr; Zpět na zprávy</a></p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <?php if ($messages === []): ?>
        <p class="muted">Zatím žádné zprávy.</p>
    <?php else: ?>
        <?php foreach ($messages as $m): ?>
            <div style="border-top:1px solid var(--line); padding:0.5rem 0;">
                <strong><?= ($m['sender_role'] ?? '') === 'owner' ? 'Vy' : 'Výzkumný tým' ?></strong>
                <span class="muted"><?= e(\App\Support\Dates::toCzDateTime((string) $m['created_at'])) ?></span>
                <div><?= nl2br(e((string) $m['body'])) ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="post" action="/portal/messages" style="margin-top:0.75rem">
        <?= \App\Core\Csrf::field() ?>
        <?php if ($dogId > 0): ?><input type="hidden" name="dog_id" value="<?= $dogId ?>"><?php endif; ?>
        <label for="body">Nová zpráva</label>
        <textarea id="body" name="body" rows="3" required></textarea>
        <button type="submit" class="btn btn--primary">Odeslat</button>
    </form>
</div>
