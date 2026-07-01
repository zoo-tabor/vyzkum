<?php
/** @var array<string, mixed>|null $owner */
/** @var string|null $notice */
/** @var string|null $error */
$consent = $owner !== null && !empty($owner['contact_consent']);
?>
<div class="page-head"><h1>Nastavení</h1></div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Změna hesla</h2>
    <form method="post" action="/portal/settings/password" id="pw-form">
        <?= \App\Core\Csrf::field() ?>
        <label for="current_password">Současné heslo</label>
        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">

        <label for="new_password">Nové heslo <span class="muted">(alespoň 10 znaků)</span></label>
        <input type="password" id="new_password" name="new_password" required minlength="10" autocomplete="new-password">

        <label for="new_password_confirm">Nové heslo znovu</label>
        <input type="password" id="new_password_confirm" name="new_password_confirm" required minlength="10" autocomplete="new-password">

        <button type="submit" class="btn btn--primary">Změnit heslo</button>
    </form>
</div>

<div class="card">
    <h2>Souhlasy</h2>
    <form method="post" action="/portal/settings/consent">
        <?= \App\Core\Csrf::field() ?>
        <p><label>
            <input type="checkbox" name="contact_consent" value="1"<?= $consent ? ' checked' : '' ?>>
            Souhlasím se <a href="/gdpr" target="_blank" rel="noopener">zpracováním osobních údajů</a>
            pro účely výzkumné studie a s budoucím kontaktováním ohledně aktualizace informací
            týkajících se výzkumu dlouhověkosti.
        </label></p>
        <p class="muted">Souhlas můžete kdykoli odvolat odškrtnutím a uložením. Aktuální stav:
            <strong><?= $consent ? 'udělen' : 'neudělen' ?></strong>.</p>
        <button type="submit" class="btn btn--primary">Uložit souhlas</button>
    </form>
</div>

<script>
(function () {
    var f = document.getElementById('pw-form');
    if (!f) { return; }
    f.addEventListener('submit', function (e) {
        var n = document.getElementById('new_password'), c = document.getElementById('new_password_confirm');
        if (n && c && n.value !== c.value) { e.preventDefault(); alert('Nová hesla se neshodují.'); }
    });
})();
</script>
