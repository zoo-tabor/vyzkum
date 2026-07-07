<?php
/** @var array<string, mixed>|null $owner */
/** @var string $primaryEmail */
/** @var array<int, array<string, mixed>> $secondaryEmails */
/** @var array<int, array<string, mixed>> $phones */
/** @var string|null $error */

$isEdit = $owner !== null;
$action = $isEdit ? '/admin/owners/' . (int) $owner['id'] : '/admin/owners';
$v = static fn (string $key): string => e((string) ($owner[$key] ?? ''));
$pcm = (string) ($owner['preferred_contact_method'] ?? 'email');
$secondaryText = implode('; ', array_map(static fn ($e) => $e['email'], $secondaryEmails));
$phonesText = implode('; ', array_map(static fn ($p) => $p['phone'], $phones));
?>
<div class="page-head"><h1><?= $isEdit ? t('Upravit majitele') : t('Nový majitel') ?></h1></div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="<?= e($action) ?>">
        <?= \App\Core\Csrf::field() ?>

        <label for="display_name"><?= t('Zobrazované jméno') ?> *</label>
        <input type="text" id="display_name" name="display_name" value="<?= $isEdit ? $v('display_name') : old('display_name') ?>" required>

        <div class="form-row">
            <div><label for="first_name"><?= t('Jméno') ?></label>
                <input type="text" id="first_name" name="first_name" value="<?= $v('first_name') ?>"></div>
            <div><label for="last_name"><?= t('Příjmení') ?></label>
                <input type="text" id="last_name" name="last_name" value="<?= $v('last_name') ?>"></div>
            <div><label for="preferred_contact_method"><?= t('Preferovaný kontakt') ?></label>
                <select id="preferred_contact_method" name="preferred_contact_method">
                    <option value="email"<?= $pcm === 'email' ? ' selected' : '' ?>><?= t('e-mail') ?></option>
                    <option value="phone"<?= $pcm === 'phone' ? ' selected' : '' ?>><?= t('telefon') ?></option>
                </select></div>
        </div>

        <label for="address"><?= t('Adresa') ?></label>
        <input type="text" id="address" name="address" value="<?= $v('address') ?>">

        <label for="primary_email"><?= t('Primární e-mail') ?></label>
        <input type="email" id="primary_email" name="primary_email" value="<?= e($primaryEmail) ?>">
        <?php if ($isEdit): ?><p class="muted"><?= t('Primární e-mail je zároveň přihlašovací jméno majitele do portálu.') ?></p><?php endif; ?>

        <label for="secondary_emails"><?= t('Další e-maily (oddělte středníkem)') ?></label>
        <input type="text" id="secondary_emails" name="secondary_emails" value="<?= e($secondaryText) ?>">

        <label for="phones"><?= t('Telefony (oddělte středníkem)') ?></label>
        <input type="text" id="phones" name="phones" value="<?= e($phonesText) ?>">

        <label class="inline"><input type="checkbox" name="contact_consent" value="1"<?= !empty($owner['contact_consent']) ? ' checked' : '' ?>> <?= t('Souhlas s kontaktováním') ?></label>

        <label for="note"><?= t('Poznámka') ?></label>
        <textarea id="note" name="note" rows="2"><?= $v('note') ?></textarea>

        <button type="submit" class="btn btn--primary"><?= $isEdit ? t('Uložit změny') : t('Vytvořit majitele') ?></button>
        <a class="btn" href="<?= $isEdit ? '/admin/owners/' . (int) $owner['id'] : '/admin/owners' ?>"><?= t('Zrušit') ?></a>
    </form>
</div>
