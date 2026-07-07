<?php
/** @var array<string, mixed> $sample */
/** @var array<int, array<string, mixed>> $breeds */
/** @var array<int, string> $errors */
$chip = $sample['chip_number'] ?? $sample['chip_number_vet'] ?? '';
$chipLocked = !empty($sample['chip_number_vet']);
$sex = $sample['sex'] ?? 'unknown';
?>
<div class="card">
    <h1><?= t('Registrace psa') ?></h1>
    <p><?= t('Vzorek') ?> <span class="sample-code"><?= e($sample['sample_id']) ?></span></p>

    <?php if ($errors !== []): ?><div class="alert alert--error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <?= \App\Core\Csrf::field() ?>

        <fieldset>
            <legend><?= t('Pes') ?></legend>
            <?php if (empty($sample['breed_id'])): ?>
                <label for="breed_id"><?= t('Plemeno') ?></label>
                <select id="breed_id" name="breed_id" required>
                    <option value="">- <?= t('vyberte') ?> -</option>
                    <?php foreach ($breeds as $b): ?>
                        <option value="<?= (int) $b['id'] ?>"<?= (int) ($sample['breed_id'] ?? 0) === (int) $b['id'] ? ' selected' : '' ?>><?= e(\App\Support\Breeds::translate($b['name'])) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <p><?= t('Plemeno:') ?> <strong><?= e(\App\Support\Breeds::translate($sample['breed_name'] ?? '')) ?></strong></p>
            <?php endif; ?>

            <label for="chip_number"><?= t('Číslo čipu psa') ?></label>
            <input type="text" id="chip_number" name="chip_number" required value="<?= e($chip) ?>" <?= $chipLocked ? 'readonly' : '' ?>>

            <label for="dog_name"><?= t('Jméno psa') ?></label>
            <input type="text" id="dog_name" name="dog_name" required value="<?= e($sample['dog_name'] ?? '') ?>">

            <label for="sex"><?= t('Pohlaví') ?></label>
            <select id="sex" name="sex">
                <option value="unknown"<?= $sex === 'unknown' ? ' selected' : '' ?>><?= tc('pohlaví', 'Neuvedeno') ?></option>
                <option value="male"<?= $sex === 'male' ? ' selected' : '' ?>><?= tc('pohlaví', 'Pes') ?></option>
                <option value="female"<?= $sex === 'female' ? ' selected' : '' ?>><?= tc('pohlaví', 'Fena') ?></option>
            </select>

            <label for="birth_date"><?= t('Datum narození') ?></label>
            <input type="date" id="birth_date" name="birth_date" required value="<?= e($sample['birth_date'] ?? '') ?>">

            <label for="pedigree_number"><?= t('Číslo zápisu / průkazu původu') ?></label>
            <input type="text" id="pedigree_number" name="pedigree_number" required value="<?= e($sample['pedigree_number'] ?? '') ?>">
        </fieldset>

        <fieldset>
            <legend><?= t('Rodokmen') ?></legend>
            <label for="pedigree"><?= t('Foto / sken průkazu původu') ?></label>
            <input type="file" id="pedigree" name="pedigree" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
            <p class="muted"><?= t('PDF, JPG, PNG nebo WEBP do 10 MB.') ?></p>
        </fieldset>

        <fieldset>
            <legend><?= t('Kontakt majitele') ?></legend>
            <label for="owner_name"><?= t('Jméno a příjmení') ?></label>
            <input type="text" id="owner_name" name="owner_name" required value="<?= e($sample['owner_name'] ?? '') ?>">

            <label for="owner_email"><?= t('E-mail') ?></label>
            <input type="email" id="owner_email" name="owner_email" required value="<?= e($sample['owner_email'] ?? '') ?>">
            <p class="muted"><?= t('Na tento e-mail přijde odkaz pro nastavení hesla do portálu.') ?></p>

            <label for="owner_phone"><?= t('Telefon (nepovinné)') ?></label>
            <input type="text" id="owner_phone" name="owner_phone" value="<?= e($sample['owner_phone'] ?? '') ?>">

            <label for="owner_address"><?= t('Adresa (nepovinné)') ?></label>
            <input type="text" id="owner_address" name="owner_address" value="<?= e($sample['owner_address'] ?? '') ?>">
        </fieldset>

        <fieldset>
            <legend><?= t('Souhlas') ?></legend>
            <p><label><input type="checkbox" name="main_consent" value="1" required> <?= t('Uděluji {link} pro účely výzkumné studie dlouhověkosti psů.', [
                'link' => '<a href="/gdpr" target="_blank" rel="noopener">' . t('informovaný souhlas se zpracováním osobních a genetických údajů') . '</a>',
            ]) ?></label></p>
        </fieldset>

        <button type="submit" class="btn btn--primary"><?= t('Odeslat registraci') ?></button>
    </form>
</div>
