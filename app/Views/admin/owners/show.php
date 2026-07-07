<?php
/** @var array<string, mixed> $owner */
/** @var array<int, array<string, mixed>> $emails */
/** @var array<int, array<string, mixed>> $phones */
/** @var array<int, array<string, mixed>> $dogs */
?>
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
    <h1><?= e($owner['display_name']) ?></h1>
    <span style="display:flex; gap:0.5rem; align-items:center;">
        <a class="btn" href="/admin/owners/<?= (int) $owner['id'] ?>/edit"><?= t('Upravit') ?></a>
        <form method="post" action="/admin/owners/<?= (int) $owner['id'] ?>/delete" style="margin:0;"
              onsubmit="return confirm(<?= e(json_encode(t('Opravdu smazat majitele „{name}“ a jeho přihlašovací účet? Tuto akci nelze vzít zpět.', ['name' => $owner['display_name']]), JSON_UNESCAPED_UNICODE)) ?>);">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn--danger"><?= t('Smazat') ?></button>
        </form>
    </span>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2><?= t('Účet a přihlášení') ?></h2>
    <?php if (empty($primaryEmail)): ?>
        <p class="muted"><?= t('Majitel nemá primární e-mail. Doplňte ho, aby šlo odeslat pozvánku pro nastavení hesla.') ?></p>
    <?php elseif (!empty($account['has_password'])): ?>
        <p><?= t('Majitel má aktivní účet (heslo nastaveno). Přihlašuje se e-mailem {email}.', ['email' => '<strong>' . e($primaryEmail) . '</strong>']) ?></p>
    <?php else: ?>
        <?php if (!empty($account['has_invite']) && empty($account['invite_expired'])): ?>
            <p class="muted"><?= t('Pozvánka byla odeslána na {email} a čeká na nastavení hesla.', ['email' => e($primaryEmail)]) ?></p>
        <?php elseif (!empty($account['invite_expired'])): ?>
            <p class="muted"><?= t('Předchozí pozvánka vypršela.') ?></p>
        <?php endif; ?>
        <form method="post" action="/admin/owners/<?= (int) $owner['id'] ?>/send-password">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn--primary">
                <?= !empty($account['has_invite']) ? t('Odeslat heslo znovu') : t('Odeslat heslo') ?>
            </button>
            <span class="muted"><?= t('Pošle na {email} odkaz pro nastavení hesla (platí 1 měsíc).', ['email' => e($primaryEmail)]) ?></span>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <h2><?= t('Kontaktní údaje') ?></h2>
    <table class="table">
        <tr><th style="width:200px"><?= t('Jméno') ?></th><td><?= e(trim(($owner['first_name'] ?? '') . ' ' . ($owner['last_name'] ?? ''))) ?: e($owner['display_name']) ?></td></tr>
        <tr><th><?= t('Adresa') ?></th><td><?= e($owner['address'] ?? '') ?></td></tr>
        <tr><th><?= t('Preferovaný kontakt') ?></th><td><?= e($owner['preferred_contact_method']) ?></td></tr>
        <tr><th><?= t('Preferovaný jazyk') ?></th><td>
            <?php if (!empty($owner['language'])): ?><?= e(\App\Support\I18n::name($owner['language'])) ?>
            <?php else: ?><span class="muted"><?= t('neurčeno') ?></span><?php endif; ?>
        </td></tr>
        <tr><th><?= t('Souhlas s kontaktem') ?></th><td><?= ((int) $owner['contact_consent']) === 1 ? t('ano') : t('ne') ?></td></tr>
    </table>

    <h3><?= t('E-maily') ?></h3>
    <?php if ($emails === []): ?><p class="muted"><?= t('Žádné e-maily.') ?></p><?php else: ?>
        <ul>
            <?php foreach ($emails as $em): ?>
                <li><?= e($em['email']) ?><?= ((int) $em['is_primary']) === 1 ? ' <strong>(' . t('primární') . ')</strong>' : '' ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h3><?= t('Telefony') ?></h3>
    <?php if ($phones === []): ?><p class="muted"><?= t('Žádné telefony.') ?></p><?php else: ?>
        <ul>
            <?php foreach ($phones as $ph): ?>
                <li><?= e(\App\Support\Phone::formatCz($ph['phone'])) ?><?= !empty($ph['label']) ? ' (' . e($ph['label']) . ')' : '' ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if (!empty($owner['note'])): ?><p><strong><?= t('Poznámka:') ?></strong><br><?= nl2br(e($owner['note'])) ?></p><?php endif; ?>
</div>

<div class="card">
    <h2><?= t('Psi') ?></h2>
    <?php if ($dogs === []): ?><p class="muted"><?= t('Tento majitel nemá žádné psy.') ?></p><?php else: ?>
        <table class="table">
            <thead><tr><th><?= t('Jméno') ?></th><th><?= t('Plemeno') ?></th><th><?= t('Vztah') ?></th></tr></thead>
            <tbody>
            <?php foreach ($dogs as $d): ?>
                <tr>
                    <td><a href="/admin/dogs/<?= (int) $d['id'] ?>"><?= e($d['name']) ?></a></td>
                    <td><?= e(\App\Support\Breeds::translate($d['breed_name'])) ?></td>
                    <td><?= ((int) $d['is_current']) === 1 ? t('aktuální') : t('bývalý') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<p><a href="/admin/owners">&larr; <?= t('Zpět na seznam') ?></a></p>
