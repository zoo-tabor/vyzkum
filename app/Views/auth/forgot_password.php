<?php
/** @var bool $sent */
/** @var string|null $email */
?>
<div class="card card--auth">
    <div class="auth-brand">
        <img class="auth-logo" src="/favicon/favicon.svg" width="56" height="56" alt="">
        <div class="topbar__brand auth-brandname">Výzkum <span>ZOO Tábor</span></div>
        <p class="muted">Obnova hesla</p>
    </div>

    <?php if (!empty($sent)): ?>
        <div class="alert alert--ok">
            Pokud k zadanému e-mailu existuje účet, poslali jsme na něj odkaz pro obnovu hesla.
            Zkontrolujte prosím svou schránku (i složku nevyžádané pošty).
        </div>
        <p><a href="/login">Zpět na přihlášení</a></p>
    <?php else: ?>
        <p class="muted">Zadejte e-mail, kterým se přihlašujete. Pošleme vám odkaz pro nastavení nového hesla.</p>
        <form method="post" action="/forgot-password">
            <?= \App\Core\Csrf::field() ?>
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="<?= e($email ?? '') ?>" required autofocus autocomplete="username">

            <button type="submit" class="btn btn--primary">Odeslat odkaz pro obnovu hesla</button>
        </form>
        <p class="muted" style="margin-top:1rem"><a href="/login">Zpět na přihlášení</a></p>
    <?php endif; ?>
</div>
