<?php
/** @var array<string, mixed>|null $owner */
/** @var array{thread: array<string,mixed>|null, count: int}|null $general */
/** @var array<int, array{dog: array<string,mixed>, thread: array<string,mixed>|null, count: int}> $dogs */
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
        <p class="muted">Vyberte konverzaci. Obecná zpráva se neváže ke konkrétnímu psovi.</p>
        <table class="table">
            <thead><tr><th>Konverzace</th><th>Zpráv</th><th>Poslední zpráva</th><th></th></tr></thead>
            <tbody>
                <tr>
                    <td><a href="/portal/messages/general"><strong>Obecná zpráva</strong></a></td>
                    <td><?= (int) ($general['count'] ?? 0) ?></td>
                    <td><?= e($lastAt($general['thread'] ?? null)) ?></td>
                    <td><a href="/portal/messages/general">Otevřít &rarr;</a></td>
                </tr>
                <?php foreach ($dogs as $row): $d = $row['dog']; $did = (int) $d['id']; ?>
                    <tr>
                        <td><a href="/portal/messages/<?= $did ?>"><?= e($d['name']) ?></a>
                            <span class="muted">/ <?= e($d['breed_name'] ?? '') ?></span></td>
                        <td><?= (int) $row['count'] ?></td>
                        <td><?= e($lastAt($row['thread'])) ?></td>
                        <td><a href="/portal/messages/<?= $did ?>">Otevřít &rarr;</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
