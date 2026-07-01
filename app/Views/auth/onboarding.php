<?php
/** @var array<string, mixed> $owner */
/** @var string|null $primaryEmail */
/** @var array<int, array<string, mixed>> $secondaryEmails */
/** @var array<int, array<string, mixed>> $phones */
/** @var array<int, array<string, mixed>> $dogs */
/** @var string $token */
/** @var string|null $error */
/** @var array<string, mixed> $old */

$old = $old ?? [];
$val = static fn (string $k, string $default = '') => isset($old[$k]) ? (string) $old[$k] : $default;
$phoneList = $val('phones', implode('; ', array_map(static fn ($p) => $p['phone'], $phones)));
$emailList = $val('secondary_emails', implode('; ', array_map(static fn ($e) => $e['email'], $secondaryEmails)));
?>
<div class="page-head">
    <h1>Vítejte – dokončení registrace</h1>
    <p class="muted">Projděte prosím své údaje a psy, potvrďte souhlas a na závěr si nastavte heslo do portálu.</p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<form method="post" action="/set-password/<?= e($token) ?>" id="onboarding-form">
    <?= \App\Core\Csrf::field() ?>

    <div class="card">
        <h2>1. Kontaktní údaje</h2>
        <p>Primární e-mail (přihlašovací): <strong><?= e($primaryEmail ?? '') ?></strong>
            <br><span class="muted">Změnu primárního e-mailu zařídí výzkumný tým.</span></p>

        <label for="address">Adresa</label>
        <input type="text" id="address" name="address" value="<?= e($val('address', (string) ($owner['address'] ?? ''))) ?>">

        <label for="phones">Telefon(y) <span class="muted">(více oddělte středníkem)</span></label>
        <input type="text" id="phones" name="phones" value="<?= e($phoneList) ?>" placeholder="+420...">

        <label for="secondary_emails">Další e-mail(y) <span class="muted">(nepovinné, oddělte středníkem)</span></label>
        <input type="text" id="secondary_emails" name="secondary_emails" value="<?= e($emailList) ?>">
    </div>

    <div class="card">
        <h2>2. Vaši psi</h2>
        <?php if ($dogs === []): ?>
            <p class="muted">Zatím u vás nemáme evidované žádné psy.</p>
        <?php else: ?>
            <?php foreach ($dogs as $d): $id = (int) $d['id']; $dec = $val('dog_' . $id); ?>
                <fieldset class="onb-dog">
                    <legend><?= e($d['name']) ?> <span class="muted">(<?= e($d['breed_name']) ?>)</span></legend>
                    <p>Jste stále majitelem tohoto psa?</p>
                    <p>
                        <label class="inline"><input type="radio" name="dog_<?= $id ?>" value="keep" required<?= $dec === 'keep' ? ' checked' : '' ?>> Ano</label>
                        &nbsp;&nbsp;
                        <label class="inline"><input type="radio" name="dog_<?= $id ?>" value="transfer"<?= $dec === 'transfer' ? ' checked' : '' ?>> Ne, psa převzal někdo jiný</label>
                    </p>
                    <div class="onb-new-owner" id="new-owner-<?= $id ?>"<?= $dec === 'transfer' ? '' : ' hidden' ?>>
                        <p class="muted">Zadejte prosím kontakt na nového majitele – pošleme mu odkaz k potvrzení převzetí.</p>
                        <label for="new_owner_name_<?= $id ?>">Jméno nového majitele</label>
                        <input type="text" id="new_owner_name_<?= $id ?>" name="new_owner_name_<?= $id ?>" value="<?= e($val('new_owner_name_' . $id)) ?>">
                        <label for="new_owner_email_<?= $id ?>">E-mail nového majitele</label>
                        <input type="email" id="new_owner_email_<?= $id ?>" name="new_owner_email_<?= $id ?>" value="<?= e($val('new_owner_email_' . $id)) ?>">
                        <label for="new_owner_phone_<?= $id ?>">Telefon nového majitele <span class="muted">(nepovinné)</span></label>
                        <input type="text" id="new_owner_phone_<?= $id ?>" name="new_owner_phone_<?= $id ?>" value="<?= e($val('new_owner_phone_' . $id)) ?>">
                    </div>
                </fieldset>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>3. Souhlas</h2>
        <p><label><input type="checkbox" name="main_consent" value="1" required<?= !empty($old['main_consent']) ? ' checked' : '' ?>>
            Souhlasím se <a href="/gdpr" target="_blank" rel="noopener">zpracováním osobních údajů</a>
            pro účely výzkumné studie a s budoucím kontaktováním ohledně aktualizace informací
            týkajících se výzkumu dlouhověkosti.</label></p>
    </div>

    <div class="card">
        <h2>4. Heslo do portálu</h2>
        <p class="muted">Zvolte si heslo (alespoň 10 znaků).</p>
        <label for="password">Nové heslo</label>
        <input type="password" id="password" name="password" required minlength="10" autocomplete="new-password">

        <label for="password_confirm">Heslo znovu</label>
        <input type="password" id="password_confirm" name="password_confirm" required minlength="10" autocomplete="new-password">

        <button type="submit" class="btn btn--primary">Dokončit registraci a nastavit heslo</button>
    </div>
</form>

<script>
(function () {
    document.querySelectorAll('.onb-dog').forEach(function (fs) {
        var box = fs.querySelector('.onb-new-owner');
        if (!box) { return; }
        var name = document.getElementById(box.id.replace('new-owner-', 'new_owner_name_'));
        var email = document.getElementById(box.id.replace('new-owner-', 'new_owner_email_'));
        function sync() {
            var transfer = fs.querySelector('input[value=transfer]').checked;
            box.hidden = !transfer;
            [name, email].forEach(function (f) { if (!f) { return; } if (transfer) { f.setAttribute('required', 'required'); } else { f.removeAttribute('required'); } });
        }
        fs.querySelectorAll('input[type=radio]').forEach(function (r) { r.addEventListener('change', sync); });
        sync();
    });
    var form = document.getElementById('onboarding-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            var p = document.getElementById('password'), c = document.getElementById('password_confirm');
            if (p && c && p.value !== c.value) { e.preventDefault(); alert('Hesla se neshodují.'); }
        });
    }
})();
</script>
