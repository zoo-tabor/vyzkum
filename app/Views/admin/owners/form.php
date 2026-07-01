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
<div class="page-head"><h1><?= $isEdit ? 'Upravit majitele' : 'Nový majitel' ?></h1></div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="<?= e($action) ?>">
        <?= \App\Core\Csrf::field() ?>

        <label for="display_name">Zobrazované jméno *</label>
        <input type="text" id="display_name" name="display_name" value="<?= $isEdit ? $v('display_name') : old('display_name') ?>" required>

        <div class="form-row">
            <div><label for="first_name">Jméno</label>
                <input type="text" id="first_name" name="first_name" value="<?= $v('first_name') ?>"></div>
            <div><label for="last_name">Příjmení</label>
                <input type="text" id="last_name" name="last_name" value="<?= $v('last_name') ?>"></div>
            <div><label for="preferred_contact_method">Preferovaný kontakt</label>
                <select id="preferred_contact_method" name="preferred_contact_method">
                    <option value="email"<?= $pcm === 'email' ? ' selected' : '' ?>>e-mail</option>
                    <option value="phone"<?= $pcm === 'phone' ? ' selected' : '' ?>>telefon</option>
                </select></div>
        </div>

        <label for="address">Adresa</label>
        <input type="text" id="address" name="address" value="<?= $v('address') ?>">

        <label for="primary_email">Primární e-mail</label>
        <input type="email" id="primary_email" name="primary_email" value="<?= e($primaryEmail) ?>">
        <?php if ($isEdit): ?><p class="muted">Primární e-mail je zároveň přihlašovací jméno majitele do portálu.</p><?php endif; ?>

        <label for="secondary_emails">Další e-maily (oddělte středníkem)</label>
        <input type="text" id="secondary_emails" name="secondary_emails" value="<?= e($secondaryText) ?>">

        <label for="phones">Telefony (oddělte středníkem)</label>
        <input type="text" id="phones" name="phones" value="<?= e($phonesText) ?>">

        <label class="inline"><input type="checkbox" name="contact_consent" value="1"<?= !empty($owner['contact_consent']) ? ' checked' : '' ?>> Souhlas s kontaktováním</label>

        <label for="note">Poznámka</label>
        <textarea id="note" name="note" rows="2"><?= $v('note') ?></textarea>

        <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Uložit změny' : 'Vytvořit majitele' ?></button>
        <a class="btn" href="<?= $isEdit ? '/admin/owners/' . (int) $owner['id'] : '/admin/owners' ?>">Zrušit</a>
    </form>
</div>
