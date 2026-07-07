<?php
/** @var array<string, mixed> $sample */
/** @var array<int, string> $errors */
$vetName = trim((string) ($sample['vet_name'] ?? '') . (($sample['clinic_name'] ?? '') ? ' / ' . $sample['clinic_name'] : ''));
?>
<div class="card">
    <h1><?= t('Veterinární potvrzení odběru') ?></h1>
    <p><?= t('Vzorek') ?> <span class="sample-code"><?= e($sample['sample_id']) ?></span></p>
    <?php if ($vetName !== ''): ?><p class="muted"><?= t('Veterinář:') ?> <?= e($vetName) ?></p><?php endif; ?>

    <?php if ($errors !== []): ?><div class="alert alert--error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>

    <form method="post">
        <?= \App\Core\Csrf::field() ?>

        <label for="chip_number_vet"><?= t('Číslo čipu psa') ?></label>
        <input type="text" id="chip_number_vet" name="chip_number_vet" required value="<?= e($sample['chip_number_vet'] ?? '') ?>">

        <label for="sample_type"><?= t('Typ vzorku') ?></label>
        <select id="sample_type" name="sample_type" required>
            <?php $st = $sample['sample_type'] ?? 'buccal_swab'; ?>
            <option value="buccal_swab"<?= $st === 'buccal_swab' ? ' selected' : '' ?>><?= t('Bukální stěr') ?></option>
            <option value="blood"<?= $st === 'blood' ? ' selected' : '' ?>><?= t('Krevní vzorek') ?></option>
            <option value="other"<?= $st === 'other' ? ' selected' : '' ?>><?= t('Jiné') ?></option>
        </select>

        <label for="sample_type_other"><?= t('Jiný typ vzorku (nepovinné)') ?></label>
        <input type="text" id="sample_type_other" name="sample_type_other" value="<?= e($sample['sample_type_other'] ?? '') ?>">

        <label for="material_count"><?= t('Počet') ?></label>
        <select id="material_count" name="material_count" required>
            <?php $mc = (string) ($sample['material_count'] ?? '1'); ?>
            <?php foreach (['1', '2', '3', '4', '5', 'jine'] as $opt): ?>
                <option value="<?= e($opt) ?>"<?= $mc === $opt ? ' selected' : '' ?>><?= e($opt) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="collection_date"><?= t('Datum odběru') ?></label>
        <input type="date" id="collection_date" name="collection_date" required value="<?= e($sample['collection_date'] ?? date('Y-m-d')) ?>">

        <button type="submit" class="btn btn--primary"><?= t('Odeslat odběr') ?></button>
    </form>
</div>
