<?php
/** @var string|null $error */
?>
<div class="card card--auth">
    <h1>Dvoufaktorove overeni</h1>
    <p class="muted">Zadejte 6mistny kod z autentikatoru.</p>

    <?php if (!empty($error)): ?>
        <div class="alert alert--error"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="/2fa">
        <?= \App\Core\Csrf::field() ?>
        <label for="code">Overovaci kod</label>
        <input type="text" id="code" name="code" inputmode="numeric" autocomplete="one-time-code"
               pattern="[0-9]*" maxlength="6" required autofocus>
        <button type="submit" class="btn btn--primary">Overit</button>
    </form>

    <form method="post" action="/logout" class="inline">
        <?= \App\Core\Csrf::field() ?>
        <button type="submit" class="btn btn--ghost">Zrusit a odhlasit</button>
    </form>
</div>
