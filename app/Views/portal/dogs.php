<?php
/** @var array<string, mixed>|null $owner */
/** @var array<int, array<string, mixed>> $dogs */
/** @var array<int, array<string, mixed>> $emails */
/** @var array<int, array<string, mixed>> $phones */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head">
    <h1>Moji psi</h1>
    <?php if ($owner !== null): ?><p class="muted"><?= e($owner['display_name']) ?></p><?php endif; ?>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<?php if ($owner === null): ?>
    <div class="card">
        <p>Váš účet zatím není propojen s žádným majitelem v evidenci. Kontaktujte prosím výzkumný tým.</p>
    </div>
<?php else: ?>
    <div class="card">
        <h2>Psi</h2>
        <?php if ($dogs === []): ?>
            <p class="muted">Zatím u vás nemáme evidované žádné psy.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Jméno</th><th>Plemeno</th><th>Vztah</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($dogs as $d): ?>
                    <tr>
                        <td><a href="/portal/dogs/<?= (int) $d['id'] ?>"><?= e($d['name']) ?></a></td>
                        <td><?= e($d['breed_name']) ?></td>
                        <td><?= ((int) $d['is_current']) === 1 ? 'aktuální' : 'bývalý' ?></td>
                        <td><a href="/portal/dogs/<?= (int) $d['id'] ?>">Detail &rarr;</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Moje kontaktní údaje</h2>
        <p><strong>E-maily:</strong>
            <?= $emails === [] ? '-' : e(implode(', ', array_map(static fn ($e) => $e['email'], $emails))) ?>
        </p>
        <p><strong>Telefony:</strong>
            <?= $phones === [] ? '-' : e(implode(', ', array_map(static fn ($p) => $p['phone'], $phones))) ?>
        </p>
        <p><a class="btn" href="/portal/contacts">Upravit kontaktní údaje</a></p>
    </div>
<?php endif; ?>
