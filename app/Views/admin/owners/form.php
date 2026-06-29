<?php
/** @var string|null $error */
?>
<div class="page-head"><h1>Novy majitel</h1></div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="/admin/owners">
        <?= \App\Core\Csrf::field() ?>

        <label for="display_name">Zobrazovane jmeno *</label>
        <input type="text" id="display_name" name="display_name" value="<?= old('display_name') ?>" required>

        <div class="form-row">
            <div><label for="first_name">Jmeno</label>
                <input type="text" id="first_name" name="first_name" value="<?= old('first_name') ?>"></div>
            <div><label for="last_name">Prijmeni</label>
                <input type="text" id="last_name" name="last_name" value="<?= old('last_name') ?>"></div>
            <div><label for="preferred_contact_method">Preferovany kontakt</label>
                <select id="preferred_contact_method" name="preferred_contact_method">
                    <option value="email">e-mail</option>
                    <option value="phone">telefon</option>
                </select></div>
        </div>

        <label for="address">Adresa</label>
        <input type="text" id="address" name="address" value="<?= old('address') ?>">

        <label for="primary_email">Primarni e-mail</label>
        <input type="email" id="primary_email" name="primary_email" value="<?= old('primary_email') ?>">

        <label for="secondary_emails">Dalsi e-maily (oddelte strednikem)</label>
        <input type="text" id="secondary_emails" name="secondary_emails" value="<?= old('secondary_emails') ?>">

        <label for="phones">Telefony (oddelte strednikem)</label>
        <input type="text" id="phones" name="phones" value="<?= old('phones') ?>">

        <label class="inline"><input type="checkbox" name="contact_consent" value="1"> Souhlas s kontaktovanim</label>

        <label for="note">Poznamka</label>
        <textarea id="note" name="note" rows="2"><?= old('note') ?></textarea>

        <button type="submit" class="btn btn--primary">Vytvorit majitele</button>
        <a class="btn" href="/admin/owners">Zrusit</a>
    </form>
</div>
