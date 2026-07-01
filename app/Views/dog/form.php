<?php
/** @var array<string, mixed> $sample */
/** @var array<int, array<string, mixed>> $breeds */
/** @var array<int, string> $errors */
$chip = $sample['chip_number'] ?? $sample['chip_number_vet'] ?? '';
$chipLocked = !empty($sample['chip_number_vet']);
$sex = $sample['sex'] ?? 'unknown';
?>
<div class="card">
    <h1>Registrace psa</h1>
    <p>Vzorek <span class="sample-code"><?= e($sample['sample_id']) ?></span></p>

    <?php if ($errors !== []): ?><div class="alert alert--error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <?= \App\Core\Csrf::field() ?>

        <fieldset>
            <legend>Pes</legend>
            <?php if (empty($sample['breed_id'])): ?>
                <label for="breed_id">Plemeno</label>
                <select id="breed_id" name="breed_id" required>
                    <option value="">- vyberte -</option>
                    <?php foreach ($breeds as $b): ?>
                        <option value="<?= (int) $b['id'] ?>"<?= (int) ($sample['breed_id'] ?? 0) === (int) $b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <p>Plemeno: <strong><?= e($sample['breed_name'] ?? '') ?></strong></p>
            <?php endif; ?>

            <label for="chip_number">Číslo čipu psa</label>
            <input type="text" id="chip_number" name="chip_number" required value="<?= e($chip) ?>" <?= $chipLocked ? 'readonly' : '' ?>>

            <label for="dog_name">Jméno psa</label>
            <input type="text" id="dog_name" name="dog_name" required value="<?= e($sample['dog_name'] ?? '') ?>">

            <label for="sex">Pohlaví</label>
            <select id="sex" name="sex">
                <option value="unknown"<?= $sex === 'unknown' ? ' selected' : '' ?>>Neuvedeno</option>
                <option value="male"<?= $sex === 'male' ? ' selected' : '' ?>>Pes</option>
                <option value="female"<?= $sex === 'female' ? ' selected' : '' ?>>Fena</option>
            </select>

            <label for="birth_date">Datum narození</label>
            <input type="date" id="birth_date" name="birth_date" required value="<?= e($sample['birth_date'] ?? '') ?>">

            <label for="pedigree_number">Číslo zápisu / průkazu původu</label>
            <input type="text" id="pedigree_number" name="pedigree_number" required value="<?= e($sample['pedigree_number'] ?? '') ?>">
        </fieldset>

        <fieldset>
            <legend>Rodokmen</legend>
            <label for="pedigree">Foto / sken průkazu původu</label>
            <input type="file" id="pedigree" name="pedigree" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
            <p class="muted">PDF, JPG, PNG nebo WEBP do 10 MB.</p>
        </fieldset>

        <fieldset>
            <legend>Kontakt majitele</legend>
            <label for="owner_name">Jméno a příjmení</label>
            <input type="text" id="owner_name" name="owner_name" required value="<?= e($sample['owner_name'] ?? '') ?>">

            <label for="owner_email">E-mail</label>
            <input type="email" id="owner_email" name="owner_email" required value="<?= e($sample['owner_email'] ?? '') ?>">
            <p class="muted">Na tento e-mail přijde odkaz pro nastavení hesla do portálu.</p>

            <label for="owner_phone">Telefon (nepovinné)</label>
            <input type="text" id="owner_phone" name="owner_phone" value="<?= e($sample['owner_phone'] ?? '') ?>">

            <label for="owner_address">Adresa (nepovinné)</label>
            <input type="text" id="owner_address" name="owner_address" value="<?= e($sample['owner_address'] ?? '') ?>">
        </fieldset>

        <fieldset>
            <legend>Souhlas</legend>
            <p><label><input type="checkbox" name="main_consent" value="1" required> Souhlasím se <a href="/gdpr" target="_blank" rel="noopener">zpracováním osobních a genetických údajů</a> pro účely výzkumné studie.</label></p>
            <p><label><input type="checkbox" name="future_contact_consent" value="1"> Souhlasím s budoucím kontaktováním ohledně aktualizace informací týkajících se výzkumu dlouhověkosti.</label></p>
        </fieldset>

        <button type="submit" class="btn btn--primary">Odeslat registraci</button>
    </form>
</div>
