<?php
/** @var string|null $error */
/** @var string|null $email */
?>
<div class="card card--auth">
    <div class="auth-brand">
        <img class="auth-logo" src="/favicon/favicon.svg" width="56" height="56" alt="">
        <div class="topbar__brand auth-brandname">Výzkum <span>ZOO Tábor</span></div>
        <p class="muted"><?= t('Dlouhověkost psích plemen') ?></p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/login">
        <?= \App\Core\Csrf::field() ?>
        <label for="email"><?= t('E-mail') ?></label>
        <input type="email" id="email" name="email" value="<?= e($email ?? '') ?>" required autofocus autocomplete="username">

        <label for="password"><?= t('Heslo') ?></label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <button type="submit" class="btn btn--primary"><?= t('Přihlásit se') ?></button>
    </form>
    <p class="muted" style="margin-top:1rem"><a href="/forgot-password"><?= t('Zapomněli jste heslo?') ?></a></p>
</div>
