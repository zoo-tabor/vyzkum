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
    <h1>Moje kontaktni udaje</h1>
    <p><a href="/portal">&larr; Zpet</a></p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p>Primarni e-mail (prihlasovaci): <strong><?= e($primaryEmail ?? '') ?></strong>
        <br><span class="muted">Zmenu primarniho e-mailu zaridí vyzkumny tym.</span></p>

    <form method="post" action="/portal/contacts">
        <?= \App\Core\Csrf::field() ?>

        <label for="address">Adresa</label>
        <input type="text" id="address" name="address" value="<?= e($owner['address'] ?? '') ?>">

        <label for="phones">Telefony (oddelte strednikem)</label>
        <input type="text" id="phones" name="phones" value="<?= e($phoneList) ?>">

        <label for="secondary_emails">Dalsi e-maily (oddelte strednikem)</label>
        <input type="text" id="secondary_emails" name="secondary_emails" value="<?= e($emailList) ?>">

        <button type="submit" class="btn btn--primary">Ulozit zmeny</button>
    </form>
</div>
