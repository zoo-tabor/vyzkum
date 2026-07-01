<?php
/** @var array<string, mixed>|null $owner */
/** @var array{thread: array<string,mixed>|null, count: int, unseen: bool}|null $general */
/** @var array<int, array{dog: array<string,mixed>, thread: array<string,mixed>|null, count: int, unseen: bool}> $dogs */
/** @var string|null $notice */
/** @var string|null $error */

$lastAt = static function (?array $thread): string {
    if ($thread === null || empty($thread['last_message_at'])) {
        return '-';
    }
    return \App\Support\Dates::toCzDateTime((string) $thread['last_message_at']);
};
?>
<div class="page-head"><h1>Zprávy</h1></div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<?php if ($owner === null): ?>
    <div class="card"><p>Váš účet zatím není propojen s žádným majitelem v evidenci.</p></div>
<?php else: ?>
    <div class="card">
        <p class="muted">Vyberte konverzaci. Tučně jsou vlákna s novou (dosud nezobrazenou) zprávou.</p>
        <table class="table">
            <thead><tr><th>Konverzace</th><th>Zpráv</th><th>Poslední zpráva</th><th></th></tr></thead>
            <tbody>
                <?php $gUnseen = !empty($general['unseen']); ?>
                <tr<?= $gUnseen ? ' style="font-weight:600"' : '' ?>>
                    <td><a href="/portal/messages/general">Obecná zpráva</a>
                        <?php if ($gUnseen): ?> <span class="badge-new">nové</span><?php endif; ?></td>
                    <td><?= (int) ($general['count'] ?? 0) ?></td>
                    <td><?= e($lastAt($general['thread'] ?? null)) ?></td>
                    <td><a href="/portal/messages/general">Otevřít &rarr;</a></td>
                </tr>
                <?php foreach ($dogs as $row): $d = $row['dog']; $did = (int) $d['id']; $u = !empty($row['unseen']); ?>
                    <tr<?= $u ? ' style="font-weight:600"' : '' ?>>
                        <td><a href="/portal/messages/<?= $did ?>"><?= e($d['name']) ?></a>
                            <span class="muted">/ <?= e($d['breed_name'] ?? '') ?></span>
                            <?php if ($u): ?> <span class="badge-new">nové</span><?php endif; ?></td>
                        <td><?= (int) $row['count'] ?></td>
                        <td><?= e($lastAt($row['thread'])) ?></td>
                        <td><a href="/portal/messages/<?= $did ?>">Otevřít &rarr;</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
