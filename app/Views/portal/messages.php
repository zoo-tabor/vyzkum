<?php
/** @var array<string, mixed>|null $owner */
/** @var array<int, array<string, mixed>> $general */
/** @var array<int, array{dog: array<string,mixed>, messages: array<int, array<string,mixed>>}> $dogThreads */
/** @var string|null $notice */
/** @var string|null $error */

$renderMessages = static function (array $messages): void {
    if ($messages === []) {
        echo '<p class="muted">Zatím žádné zprávy.</p>';
        return;
    }
    foreach ($messages as $m) {
        $who = ($m['sender_role'] ?? '') === 'owner' ? 'Vy' : 'Výzkumný tým';
        $when = \App\Support\Dates::toCz(substr((string) $m['created_at'], 0, 10));
        echo '<div style="border-top:1px solid var(--line); padding:0.5rem 0;">';
        echo '<strong>' . e($who) . '</strong> <span class="muted">' . e($when) . '</span>';
        echo '<div>' . nl2br(e((string) $m['body'])) . '</div>';
        echo '</div>';
    }
};
?>
<div class="page-head"><h1>Zprávy</h1></div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<?php if ($owner === null): ?>
    <div class="card"><p>Váš účet zatím není propojen s žádným majitelem v evidenci.</p></div>
<?php else: ?>
    <div class="card">
        <h2>Obecná zpráva</h2>
        <p class="muted">Zpráva, která se neváže ke konkrétnímu psovi.</p>
        <?php $renderMessages($general); ?>
        <form method="post" action="/portal/messages" style="margin-top:0.75rem">
            <?= \App\Core\Csrf::field() ?>
            <label for="body-general">Nová zpráva</label>
            <textarea id="body-general" name="body" rows="2" required></textarea>
            <button type="submit" class="btn btn--primary">Odeslat</button>
        </form>
    </div>

    <?php if ($dogThreads === []): ?>
        <div class="card"><p class="muted">Nemáte evidované žádné psy pro vlákna podle psů.</p></div>
    <?php else: ?>
        <?php foreach ($dogThreads as $t): $d = $t['dog']; $did = (int) $d['id']; ?>
            <div class="card">
                <h2><?= e($d['name']) ?> <span class="muted">/ <?= e($d['breed_name'] ?? '') ?></span></h2>
                <?php $renderMessages($t['messages']); ?>
                <form method="post" action="/portal/messages" style="margin-top:0.75rem">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="dog_id" value="<?= $did ?>">
                    <label for="body-<?= $did ?>">Nová zpráva k tomuto psovi</label>
                    <textarea id="body-<?= $did ?>" name="body" rows="2" required></textarea>
                    <button type="submit" class="btn btn--primary">Odeslat</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>
