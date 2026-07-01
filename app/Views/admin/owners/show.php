<?php
/** @var array<string, mixed> $owner */
/** @var array<int, array<string, mixed>> $emails */
/** @var array<int, array<string, mixed>> $phones */
/** @var array<int, array<string, mixed>> $dogs */
?>
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
    <h1><?= e($owner['display_name']) ?></h1>
    <a class="btn" href="/admin/owners/<?= (int) $owner['id'] ?>/edit">Upravit</a>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Účet a přihlášení</h2>
    <?php if (empty($primaryEmail)): ?>
        <p class="muted">Majitel nemá primární e-mail. Doplňte ho, aby šlo odeslat pozvánku pro nastavení hesla.</p>
    <?php elseif (!empty($account['has_password'])): ?>
        <p>Majitel má aktivní účet (heslo nastaveno). Přihlašuje se e-mailem <strong><?= e($primaryEmail) ?></strong>.</p>
    <?php else: ?>
        <?php if (!empty($account['has_invite']) && empty($account['invite_expired'])): ?>
            <p class="muted">Pozvánka byla odeslána na <?= e($primaryEmail) ?> a čeká na nastavení hesla.</p>
        <?php elseif (!empty($account['invite_expired'])): ?>
            <p class="muted">Předchozí pozvánka vypršela.</p>
        <?php endif; ?>
        <form method="post" action="/admin/owners/<?= (int) $owner['id'] ?>/send-password">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn--primary">
                <?= !empty($account['has_invite']) ? 'Odeslat heslo znovu' : 'Odeslat heslo' ?>
            </button>
            <span class="muted">Pošle na <?= e($primaryEmail) ?> odkaz pro nastavení hesla (platí 1 měsíc).</span>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Kontaktní údaje</h2>
    <table class="table">
        <tr><th style="width:200px">Jméno</th><td><?= e(trim(($owner['first_name'] ?? '') . ' ' . ($owner['last_name'] ?? ''))) ?: e($owner['display_name']) ?></td></tr>
        <tr><th>Adresa</th><td><?= e($owner['address'] ?? '') ?></td></tr>
        <tr><th>Preferovaný kontakt</th><td><?= e($owner['preferred_contact_method']) ?></td></tr>
        <tr><th>Souhlas s kontaktem</th><td><?= ((int) $owner['contact_consent']) === 1 ? 'ano' : 'ne' ?></td></tr>
    </table>

    <h3>E-maily</h3>
    <?php if ($emails === []): ?><p class="muted">Žádné e-maily.</p><?php else: ?>
        <ul>
            <?php foreach ($emails as $em): ?>
                <li><?= e($em['email']) ?><?= ((int) $em['is_primary']) === 1 ? ' <strong>(primární)</strong>' : '' ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h3>Telefony</h3>
    <?php if ($phones === []): ?><p class="muted">Žádné telefony.</p><?php else: ?>
        <ul>
            <?php foreach ($phones as $ph): ?>
                <li><?= e(\App\Support\Phone::formatCz($ph['phone'])) ?><?= !empty($ph['label']) ? ' (' . e($ph['label']) . ')' : '' ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (!empty($owner['note'])): ?><p><strong>Poznámka:</strong><br><?= nl2br(e($owner['note'])) ?></p><?php endif; ?>
</div>

<div class="card">
    <h2>Psi</h2>
    <?php if ($dogs === []): ?><p class="muted">Tento majitel nemá žádné psy.</p><?php else: ?>
        <table class="table">
            <thead><tr><th>Jméno</th><th>Plemeno</th><th>Vztah</th></tr></thead>
            <tbody>
            <?php foreach ($dogs as $d): ?>
                <tr>
                    <td><a href="/admin/dogs/<?= (int) $d['id'] ?>"><?= e($d['name']) ?></a></td>
                    <td><?= e($d['breed_name']) ?></td>
                    <td><?= ((int) $d['is_current']) === 1 ? 'aktuální' : 'bývalý' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<p><a href="/admin/owners">&larr; Zpět na seznam</a></p>
