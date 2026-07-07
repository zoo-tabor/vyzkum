<?php
/** @var array<string, mixed>|null $owner */
/** @var array<int, array<string, mixed>> $dogs */
/** @var array<int, array<string, mixed>> $emails */
/** @var array<int, array<string, mixed>> $phones */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head">
    <h1><?= t('Moji psi') ?></h1>
    <?php if ($owner !== null): ?><p class="muted"><?= e($owner['display_name']) ?></p><?php endif; ?>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<?php if ($owner === null): ?>
    <div class="card">
        <p><?= t('Váš účet zatím není propojen s žádným majitelem v evidenci. Kontaktujte prosím výzkumný tým.') ?></p>
    </div>
<?php else: ?>
    <?php if (empty($owner['onboarding_completed_at'])): ?>
        <div class="alert alert--ok">
            <?= t('Zkontrolujte prosím své údaje a potvrďte u svých psů, že jste stále jejich majitelem.') ?>
            <a href="/portal/onboarding"><?= t('Přejít na kontrolu údajů') ?> &rarr;</a>
        </div>
    <?php endif; ?>
    <div class="card">
        <h2><?= t('Psi') ?></h2>
        <?php if ($dogs === []): ?>
            <p class="muted"><?= t('Zatím u vás nemáme evidované žádné psy.') ?></p>
        <?php else: ?>
            <table class="table">
                <thead><tr><th><?= t('Jméno') ?></th><th><?= t('Plemeno') ?></th><th><?= t('Poslední aktualizace') ?></th><th></th></tr></thead>
                <tbody>
                <?php foreach ($dogs as $d): ?>
                    <tr>
                        <td><a href="/portal/dogs/<?= (int) $d['id'] ?>"><?= e($d['name']) ?></a></td>
                        <td><?= e($d['breed_name']) ?></td>
                        <td><?= e(\App\Support\Dates::toCz(substr((string) ($d['updated_at'] ?? ''), 0, 10))) ?: '-' ?></td>
                        <td><a href="/portal/dogs/<?= (int) $d['id'] ?>"><?= t('Detail') ?> &rarr;</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2><?= t('Moje kontaktní údaje') ?></h2>
        <p><strong><?= t('E-maily:') ?></strong>
            <?= $emails === [] ? '-' : e(implode(', ', array_map(static fn ($e) => $e['email'], $emails))) ?>
        </p>
        <p><strong><?= t('Telefony:') ?></strong>
            <?= $phones === [] ? '-' : e(implode(', ', array_map(static fn ($p) => $p['phone'], $phones))) ?>
        </p>
        <p><a class="btn" href="/portal/contacts"><?= t('Upravit kontaktní údaje') ?></a></p>
    </div>
<?php endif; ?>
