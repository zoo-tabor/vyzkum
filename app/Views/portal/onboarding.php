<?php
/** @var array<string, mixed> $owner */
/** @var string|null $primaryEmail */
/** @var array<int, array<string, mixed>> $secondaryEmails */
/** @var array<int, array<string, mixed>> $phones */
/** @var array<int, array<string, mixed>> $dogs */
/** @var string|null $error */

$phoneList = implode('; ', array_map(static fn ($p) => $p['phone'], $phones));
$emailList = implode('; ', array_map(static fn ($e) => $e['email'], $secondaryEmails));
?>
<div class="page-head">
    <h1><?= t('Vítejte – zkontrolujte prosím své údaje') ?></h1>
    <p class="muted"><?= t('Než začnete, ověřte prosím kontaktní údaje a u každého psa potvrďte, zda jste stále jeho majitelem.') ?></p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<form method="post" action="/portal/onboarding" id="onboarding-form">
    <?= \App\Core\Csrf::field() ?>

    <div class="card">
        <h2><?= t('Kontaktní údaje') ?></h2>
        <p><?= t('Primární e-mail (přihlašovací):') ?> <strong><?= e($primaryEmail ?? '') ?></strong>
            <br><span class="muted"><?= t('Změnu primárního e-mailu zařídí výzkumný tým.') ?></span></p>

        <label for="address"><?= t('Adresa') ?></label>
        <input type="text" id="address" name="address" value="<?= e($owner['address'] ?? '') ?>">

        <label for="phones"><?= t('Telefon(y)') ?> <span class="muted"><?= t('(více oddělte středníkem)') ?></span></label>
        <input type="text" id="phones" name="phones" value="<?= e($phoneList) ?>" placeholder="+420...">

        <label for="secondary_emails"><?= t('Další e-mail(y)') ?> <span class="muted"><?= t('(nepovinné, oddělte středníkem)') ?></span></label>
        <input type="text" id="secondary_emails" name="secondary_emails" value="<?= e($emailList) ?>">
    </div>

    <div class="card">
        <h2><?= t('Vaši psi') ?></h2>
        <?php if ($dogs === []): ?>
            <p class="muted"><?= t('Zatím u vás nemáme evidované žádné psy.') ?></p>
        <?php else: ?>
            <?php foreach ($dogs as $d): $id = (int) $d['id']; ?>
                <fieldset class="onb-dog">
                    <legend><?= e($d['name']) ?> <span class="muted">(<?= e($d['breed_name']) ?>)</span></legend>
                    <p><?= t('Jste stále majitelem tohoto psa?') ?></p>
                    <p>
                        <label class="inline"><input type="radio" name="dog_<?= $id ?>" value="keep" required> <?= t('Ano') ?></label>
                        &nbsp;&nbsp;
                        <label class="inline"><input type="radio" name="dog_<?= $id ?>" value="transfer"> <?= t('Ne, psa převzal někdo jiný') ?></label>
                    </p>
                    <div class="onb-new-owner" id="new-owner-<?= $id ?>" hidden>
                        <p class="muted"><?= t('Zadejte prosím kontakt na nového majitele – pošleme mu odkaz k potvrzení převzetí.') ?></p>
                        <label for="new_owner_name_<?= $id ?>"><?= t('Jméno nového majitele') ?></label>
                        <input type="text" id="new_owner_name_<?= $id ?>" name="new_owner_name_<?= $id ?>">
                        <label for="new_owner_email_<?= $id ?>"><?= t('E-mail nového majitele') ?></label>
                        <input type="email" id="new_owner_email_<?= $id ?>" name="new_owner_email_<?= $id ?>">
                        <label for="new_owner_phone_<?= $id ?>"><?= t('Telefon nového majitele') ?> <span class="muted"><?= t('(nepovinné)') ?></span></label>
                        <input type="text" id="new_owner_phone_<?= $id ?>" name="new_owner_phone_<?= $id ?>">
                    </div>
                </fieldset>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2><?= t('Souhlas') ?></h2>
        <p><label><input type="checkbox" name="main_consent" value="1" required>
            <?= t('Souhlasím se {link} pro účely výzkumné studie a s budoucím kontaktováním ohledně aktualizace informací týkajících se výzkumu dlouhověkosti.', [
                'link' => '<a href="/gdpr" target="_blank" rel="noopener">' . t('zpracováním osobních údajů') . '</a>',
            ]) ?></label></p>
    </div>

    <div class="card">
        <button type="submit" class="btn btn--primary"><?= t('Uložit a pokračovat') ?></button>
    </div>
</form>

<script>
(function () {
    document.querySelectorAll('.onb-dog').forEach(function (fs) {
        var box = fs.querySelector('.onb-new-owner');
        if (!box) { return; }
        var reqFields = box.querySelectorAll('#' + box.id.replace('new-owner-', 'new_owner_name_') + ', ' +
            '#' + box.id.replace('new-owner-', 'new_owner_email_'));
        fs.querySelectorAll('input[type=radio]').forEach(function (r) {
            r.addEventListener('change', function () {
                var transfer = fs.querySelector('input[value=transfer]').checked;
                box.hidden = !transfer;
                reqFields.forEach(function (f) { if (transfer) { f.setAttribute('required', 'required'); } else { f.removeAttribute('required'); } });
            });
        });
    });
})();
</script>
