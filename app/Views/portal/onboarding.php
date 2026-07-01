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
    <h1>Vítejte – zkontrolujte prosím své údaje</h1>
    <p class="muted">Než začnete, ověřte prosím kontaktní údaje a u každého psa potvrďte, zda jste stále jeho majitelem.</p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<form method="post" action="/portal/onboarding" id="onboarding-form">
    <?= \App\Core\Csrf::field() ?>

    <div class="card">
        <h2>Kontaktní údaje</h2>
        <p>Primární e-mail (přihlašovací): <strong><?= e($primaryEmail ?? '') ?></strong>
            <br><span class="muted">Změnu primárního e-mailu zařídí výzkumný tým.</span></p>

        <label for="address">Adresa</label>
        <input type="text" id="address" name="address" value="<?= e($owner['address'] ?? '') ?>">

        <label for="phones">Telefon(y) <span class="muted">(více oddělte středníkem)</span></label>
        <input type="text" id="phones" name="phones" value="<?= e($phoneList) ?>" placeholder="+420...">

        <label for="secondary_emails">Další e-mail(y) <span class="muted">(nepovinné, oddělte středníkem)</span></label>
        <input type="text" id="secondary_emails" name="secondary_emails" value="<?= e($emailList) ?>">
    </div>

    <div class="card">
        <h2>Vaši psi</h2>
        <?php if ($dogs === []): ?>
            <p class="muted">Zatím u vás nemáme evidované žádné psy.</p>
        <?php else: ?>
            <?php foreach ($dogs as $d): $id = (int) $d['id']; ?>
                <fieldset class="onb-dog">
                    <legend><?= e($d['name']) ?> <span class="muted">(<?= e($d['breed_name']) ?>)</span></legend>
                    <p>Jste stále majitelem tohoto psa?</p>
                    <p>
                        <label class="inline"><input type="radio" name="dog_<?= $id ?>" value="keep" required> Ano</label>
                        &nbsp;&nbsp;
                        <label class="inline"><input type="radio" name="dog_<?= $id ?>" value="transfer"> Ne, psa převzal někdo jiný</label>
                    </p>
                    <div class="onb-new-owner" id="new-owner-<?= $id ?>" hidden>
                        <p class="muted">Zadejte prosím kontakt na nového majitele – pošleme mu odkaz k potvrzení převzetí.</p>
                        <label for="new_owner_name_<?= $id ?>">Jméno nového majitele</label>
                        <input type="text" id="new_owner_name_<?= $id ?>" name="new_owner_name_<?= $id ?>">
                        <label for="new_owner_email_<?= $id ?>">E-mail nového majitele</label>
                        <input type="email" id="new_owner_email_<?= $id ?>" name="new_owner_email_<?= $id ?>">
                        <label for="new_owner_phone_<?= $id ?>">Telefon nového majitele <span class="muted">(nepovinné)</span></label>
                        <input type="text" id="new_owner_phone_<?= $id ?>" name="new_owner_phone_<?= $id ?>">
                    </div>
                </fieldset>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Souhlas</h2>
        <p><label><input type="checkbox" name="main_consent" value="1" required>
            Souhlasím se <a href="/gdpr" target="_blank" rel="noopener">zpracováním osobních údajů</a>
            pro účely výzkumné studie a s budoucím kontaktováním ohledně aktualizace informací
            týkajících se výzkumu dlouhověkosti.</label></p>
    </div>

    <div class="card">
        <button type="submit" class="btn btn--primary">Uložit a pokračovat</button>
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
