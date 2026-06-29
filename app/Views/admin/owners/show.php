<?php
/** @var array<string, mixed> $owner */
/** @var array<int, array<string, mixed>> $emails */
/** @var array<int, array<string, mixed>> $phones */
/** @var array<int, array<string, mixed>> $dogs */
?>
<div class="page-head"><h1><?= e($owner['display_name']) ?></h1></div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Ucet a prihlaseni</h2>
    <?php if (empty($primaryEmail)): ?>
        <p class="muted">Majitel nema primarni e-mail. Doplnte ho, aby slo odeslat pozvanku pro nastaveni hesla.</p>
    <?php elseif (!empty($account['has_password'])): ?>
        <p>Majitel ma aktivni ucet (heslo nastaveno). Prihlasuje se e-mailem <strong><?= e($primaryEmail) ?></strong>.</p>
    <?php else: ?>
        <?php if (!empty($account['has_invite']) && empty($account['invite_expired'])): ?>
            <p class="muted">Pozvanka byla odeslana na <?= e($primaryEmail) ?> a ceka na nastaveni hesla.</p>
        <?php elseif (!empty($account['invite_expired'])): ?>
            <p class="muted">Predchozi pozvanka vyprsela.</p>
        <?php endif; ?>
        <form method="post" action="/admin/owners/<?= (int) $owner['id'] ?>/send-password">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn--primary">
                <?= !empty($account['has_invite']) ? 'Odeslat heslo znovu' : 'Odeslat heslo' ?>
            </button>
            <span class="muted">Posle na <?= e($primaryEmail) ?> odkaz pro nastaveni hesla (plati 1 mesic).</span>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Kontaktni udaje</h2>
    <table class="table">
        <tr><th style="width:200px">Jmeno</th><td><?= e(trim(($owner['first_name'] ?? '') . ' ' . ($owner['last_name'] ?? ''))) ?: e($owner['display_name']) ?></td></tr>
        <tr><th>Adresa</th><td><?= e($owner['address'] ?? '') ?></td></tr>
        <tr><th>Preferovany kontakt</th><td><?= e($owner['preferred_contact_method']) ?></td></tr>
        <tr><th>Souhlas s kontaktem</th><td><?= ((int) $owner['contact_consent']) === 1 ? 'ano' : 'ne' ?></td></tr>
    </table>

    <h3>E-maily</h3>
    <?php if ($emails === []): ?><p class="muted">Zadne e-maily.</p><?php else: ?>
        <ul>
            <?php foreach ($emails as $em): ?>
                <li><?= e($em['email']) ?><?= ((int) $em['is_primary']) === 1 ? ' <strong>(primarni)</strong>' : '' ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h3>Telefony</h3>
    <?php if ($phones === []): ?><p class="muted">Zadne telefony.</p><?php else: ?>
        <ul>
            <?php foreach ($phones as $ph): ?>
                <li><?= e($ph['phone']) ?><?= !empty($ph['label']) ? ' (' . e($ph['label']) . ')' : '' ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (!empty($owner['note'])): ?><p><strong>Poznamka:</strong><br><?= nl2br(e($owner['note'])) ?></p><?php endif; ?>
</div>

<div class="card">
    <h2>Psi</h2>
    <?php if ($dogs === []): ?><p class="muted">Tento majitel nema zadne psy.</p><?php else: ?>
        <table class="table">
            <thead><tr><th>Jmeno</th><th>Plemeno</th><th>Vztah</th></tr></thead>
            <tbody>
            <?php foreach ($dogs as $d): ?>
                <tr>
                    <td><a href="/admin/dogs/<?= (int) $d['id'] ?>"><?= e($d['name']) ?></a></td>
                    <td><?= e($d['breed_name']) ?></td>
                    <td><?= ((int) $d['is_current']) === 1 ? 'aktualni' : 'byvaly' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<p><a href="/admin/owners">&larr; Zpet na seznam</a></p>
