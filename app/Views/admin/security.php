<?php
/** @var bool $enabled */
/** @var string|null $secret */
/** @var string|null $uri */
/** @var string|null $error */
/** @var string|null $notice */
?>
<div class="page-head">
    <h1>Zabezpeceni uctu</h1>
</div>

<?php if (!empty($notice)): ?>
    <div class="alert alert--ok"><?= e($notice) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2>Dvoufaktorove overeni (TOTP)</h2>

    <?php if ($enabled): ?>
        <p>Stav: <strong>aktivni</strong>. Pri prihlaseni je vyzadovan kod z autentikatoru.</p>
        <form method="post" action="/admin/security/disable">
            <?= \App\Core\Csrf::field() ?>
            <label for="code">Pro vypnuti zadejte aktualni kod</label>
            <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required style="max-width:200px">
            <button type="submit" class="btn">Vypnout 2FA</button>
        </form>
    <?php else: ?>
        <p class="muted">
            Naskenujte QR kod v aplikaci (Google Authenticator, Authy, 1Password apod.),
            nebo zadejte klic rucne. Pak potvrdte aktualnim kodem.
        </p>

        <div style="display:flex; gap:2rem; flex-wrap:wrap; align-items:flex-start;">
            <div>
                <div id="qrcode" style="background:#fff; padding:8px; display:inline-block;"></div>
            </div>
            <div>
                <p>Klic pro rucni zadani:</p>
                <p><code style="font-size:1.1rem; letter-spacing:1px;"><?= e($secret) ?></code></p>

                <form method="post" action="/admin/security/enable">
                    <?= \App\Core\Csrf::field() ?>
                    <label for="code">Potvrzovaci kod z aplikace</label>
                    <input type="text" id="code" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required style="max-width:200px">
                    <button type="submit" class="btn btn--primary">Aktivovat 2FA</button>
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
    <h2>Zmena hesla</h2>
    <form method="post" action="/admin/security/password" style="max-width:380px">
        <?= \App\Core\Csrf::field() ?>
        <label for="current_password">Soucasne heslo</label>
        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">

        <label for="new_password">Nove heslo (min. 10 znaku)</label>
        <input type="password" id="new_password" name="new_password" required autocomplete="new-password">

        <label for="new_password_confirm">Nove heslo znovu</label>
        <input type="password" id="new_password_confirm" name="new_password_confirm" required autocomplete="new-password">

        <button type="submit" class="btn btn--primary">Zmenit heslo</button>
    </form>
</div>
