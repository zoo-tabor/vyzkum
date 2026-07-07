<?php
/** @var bool $invalid */
/** @var string $token */
/** @var string|null $error */
?>
<div class="card card--auth">
    <h1><?= t('Nastavení hesla') ?></h1>

    <?php if (!empty($invalid)): ?>
        <div class="alert alert--error">
            <?= t('Odkaz je neplatný nebo vypršel. Požádejte výzkumný tým o nové zaslání.') ?>
        </div>
        <p><a href="/login"><?= t('Zpět na přihlášení') ?></a></p>
    <?php else: ?>
        <?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>
        <p class="muted"><?= t('Zvolte si heslo (alespoň 10 znaků).') ?></p>
        <form method="post" action="/set-password/<?= e($token) ?>">
            <?= \App\Core\Csrf::field() ?>
            <label for="password"><?= t('Nové heslo') ?></label>
            <input type="password" id="password" name="password" required autocomplete="new-password" autofocus>

            <label for="password_confirm"><?= t('Heslo znovu') ?></label>
            <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">

            <button type="submit" class="btn btn--primary"><?= t('Nastavit heslo a přihlásit') ?></button>
        </form>
    <?php endif; ?>
</div>
