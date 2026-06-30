<?php
/** @var array<string, mixed> $owner */
/** @var string|null $primaryEmail */
/** @var array<int, array<string, mixed>> $secondaryEmails */
/** @var array<int, array<string, mixed>> $phones */
/** @var string|null $notice */
/** @var string|null $error */

$phoneList = implode('; ', array_map(static fn ($p) => $p['phone'], $phones));
$emailList = implode('; ', array_map(static fn ($e) => $e['email'], $secondaryEmails));
?>
<div class="page-head">
    <h1>Moje kontaktní údaje</h1>
    <p><a href="/portal">&larr; Zpět</a></p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p>Primární e-mail (přihlašovací): <strong><?= e($primaryEmail ?? '') ?></strong>
        <br><span class="muted">Změnu primárního e-mailu zařídí výzkumný tým.</span></p>

    <form method="post" action="/portal/contacts">
        <?= \App\Core\Csrf::field() ?>

        <label for="address">Adresa</label>
        <input type="text" id="address" name="address" value="<?= e($owner['address'] ?? '') ?>">

        <label for="phones">Telefony (oddělte středníkem)</label>
        <input type="text" id="phones" name="phones" value="<?= e($phoneList) ?>">

        <label for="secondary_emails">Další e-maily (oddělte středníkem)</label>
        <input type="text" id="secondary_emails" name="secondary_emails" value="<?= e($emailList) ?>">

        <button type="submit" class="btn btn--primary">Uložit změny</button>
    </form>
</div>
