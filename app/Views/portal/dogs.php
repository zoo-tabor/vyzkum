<?php
/** @var array<string, mixed>|null $owner */
/** @var array<int, array<string, mixed>> $dogs */
/** @var array<int, array<string, mixed>> $emails */
/** @var array<int, array<string, mixed>> $phones */
?>
<div class="page-head">
    <h1>Moji psi</h1>
    <?php if ($owner !== null): ?><p class="muted"><?= e($owner['display_name']) ?></p><?php endif; ?>
</div>

<?php if ($owner === null): ?>
    <div class="card">
        <p>Vas ucet zatim neni propojen s zadnym majitelem v evidenci. Kontaktujte prosim vyzkumny tym.</p>
    </div>
<?php else: ?>
    <div class="card">
        <h2>Psi</h2>
        <?php if ($dogs === []): ?>
            <p class="muted">Zatim u vas nemame evidovane zadne psy.</p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Jmeno</th><th>Plemeno</th><th>Vztah</th></tr></thead>
                <tbody>
                <?php foreach ($dogs as $d): ?>
                    <tr>
                        <td><?= e($d['name']) ?></td>
                        <td><?= e($d['breed_name']) ?></td>
                        <td><?= ((int) $d['is_current']) === 1 ? 'aktualni' : 'byvaly' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="muted">Sprava udaju (potvrzeni psa, kontakty, dokumenty) bude doplnena v dalsi casti.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Moje kontaktni udaje</h2>
        <p><strong>E-maily:</strong>
            <?= $emails === [] ? '-' : e(implode(', ', array_map(static fn ($e) => $e['email'], $emails))) ?>
        </p>
        <p><strong>Telefony:</strong>
            <?= $phones === [] ? '-' : e(implode(', ', array_map(static fn ($p) => $p['phone'], $phones))) ?>
        </p>
    </div>
<?php endif; ?>
