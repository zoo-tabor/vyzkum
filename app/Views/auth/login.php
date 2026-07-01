<?php
/** @var string|null $error */
/** @var string|null $email */
?>
<div class="card card--auth">
    <div class="auth-brand">
        <img class="auth-logo" src="/favicon/favicon.svg" alt="">
        <div class="topbar__brand auth-brandname">Výzkum <span>ZOO Tábor</span></div>
        <p class="muted">Dlouhověkost psích plemen</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/login">
        <?= \App\Core\Csrf::field() ?>
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="<?= e($email ?? '') ?>" required autofocus autocomplete="username">

        <label for="password">Heslo</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <button type="submit" class="btn btn--primary">Přihlásit se</button>
    </form>
</div>
