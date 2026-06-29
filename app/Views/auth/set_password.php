<?php
/** @var bool $invalid */
/** @var string $token */
/** @var string|null $error */
?>
<div class="card card--auth">
    <h1>Nastaveni hesla</h1>

    <?php if (!empty($invalid)): ?>
        <div class="alert alert--error">
            Odkaz je neplatny nebo vyprsel. Pozadejte vyzkumny tym o nove zaslani.
        </div>
        <p><a href="/login">Zpet na prihlaseni</a></p>
    <?php else: ?>
        <?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>
        <p class="muted">Zvolte si heslo (aspon 10 znaku).</p>
        <form method="post" action="/set-password/<?= e($token) ?>">
            <?= \App\Core\Csrf::field() ?>
            <label for="password">Nove heslo</label>
            <input type="password" id="password" name="password" required autocomplete="new-password" autofocus>

            <label for="password_confirm">Heslo znovu</label>
            <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">

            <button type="submit" class="btn btn--primary">Nastavit heslo a prihlasit</button>
        </form>
    <?php endif; ?>
</div>
