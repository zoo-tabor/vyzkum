<?php
/** @var bool $enabled */
/** @var string|null $secret */
/** @var string|null $uri */
/** @var string|null $error */
/** @var string|null $notice */
?>
<div class="page-head">
    <h1><?= t('Zabezpečení účtu') ?></h1>
</div>

<?php if (!empty($notice)): ?>
    <div class="alert alert--ok"><?= e($notice) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2><?= t('Dvoufaktorové ověření (TOTP)') ?></h2>

    <?php if ($enabled): ?>
        <p><?= t('Stav: {state}. Při přihlášení je vyžadován kód z autentikátoru.', ['state' => '<strong>' . t('aktivní') . '</strong>']) ?></p>
        <form method="post" action="/admin/security/disable">
            <?= \App\Core\Csrf::field() ?>
            <label for="code"><?= t('Pro vypnutí zadejte aktuální kód') ?></label>
            <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required style="max-width:200px">
            <button type="submit" class="btn"><?= t('Vypnout 2FA') ?></button>
        </form>
    <?php else: ?>
        <p class="muted">
            <?= t('Naskenujte QR kód v aplikaci {app}, nebo zadejte klíč ručně. Pak potvrďte aktuálním kódem.', ['app' => '<strong>Google Authenticator</strong>']) ?>
        </p>

        <div style="display:flex; gap:2rem; flex-wrap:wrap; align-items:flex-start;">
            <div>
                <div id="qrcode" style="background:#fff; padding:8px; display:inline-block;"></div>
            </div>
            <div>
                <p><?= t('Klíč pro ruční zadání:') ?></p>
                <p><code style="font-size:1.1rem; letter-spacing:1px;"><?= e($secret) ?></code></p>

                <form method="post" action="/admin/security/enable">
                    <?= \App\Core\Csrf::field() ?>
                    <label for="code"><?= t('Potvrzovací kód z aplikace') ?></label>
                    <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required style="max-width:200px">
                    <button type="submit" class="btn btn--primary"><?= t('Aktivovat 2FA') ?></button>
                </form>
            </div>
        </div>

        <script src="/assets/vendor/qrcode.min.js"></script>
        <script>
            new QRCode(document.getElementById('qrcode'), {
                text: <?= json_encode($uri, JSON_UNESCAPED_SLASHES) ?>,
                width: 180,
                height: 180
            });
        </script>
    <?php endif; ?>
</div>

<div class="card">
    <h2><?= t('Změna hesla') ?></h2>
    <form method="post" action="/admin/security/password" style="max-width:380px">
        <?= \App\Core\Csrf::field() ?>
        <label for="current_password"><?= t('Současné heslo') ?></label>
        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">

        <label for="new_password"><?= t('Nové heslo (min. 10 znaků)') ?></label>
        <input type="password" id="new_password" name="new_password" required autocomplete="new-password">

        <label for="new_password_confirm"><?= t('Nové heslo znovu') ?></label>
        <input type="password" id="new_password_confirm" name="new_password_confirm" required autocomplete="new-password">

        <button type="submit" class="btn btn--primary"><?= t('Změnit heslo') ?></button>
    </form>
</div>

<div class="card">
    <h2><?= t('Diagnostika') ?></h2>
    <p><?= t('{link} - ověří dosažitelnost mailserveru z tohoto serveru (bez odeslání mailu).', ['link' => '<a href="/admin/diagnostics/smtp">' . t('Test SMTP spojení') . '</a>']) ?></p>
</div>
