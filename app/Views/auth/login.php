<?php
/** @var string|null $error */
/** @var string|null $email */
?>
<div class="card card--auth">
    <h1>Přihlášení</h1>
    <p class="muted">CRM pro výzkum plemen psů</p>

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
