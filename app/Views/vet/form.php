<?php use App\Core\Csrf; ?>
<section class="panel">
  <h1>Veterinarni potvrzeni odberu</h1>
  <p>Vzorek <span class="sample-code"><?= e($sample['sample_id']) ?></span></p>
  <p class="muted">
    Veterinar:
    <?= e(trim(($sample['vet_name'] ?? '') . ' ' . (($sample['clinic_name'] ?? '') ? '/ ' . $sample['clinic_name'] : '')) ?: 'predvyplni administrace') ?>
  </p>

  <?php if ($sample['vet_submitted_at'] !== null): ?>
    <div class="notice ok">Veterinarni cast uz byla ulozena. Odkaz je jednorazovy.</div>
  <?php else: ?>
    <?php if ($errors): ?>
      <div class="notice danger"><?= e(implode(' ', $errors)) ?></div>
    <?php endif; ?>

    <form method="post">
      <?= Csrf::field() ?>
      <div class="grid">
        <div>
          <label for="chip_number_vet">Cislo cipu psa</label>
          <input id="chip_number_vet" name="chip_number_vet" inputmode="numeric" pattern="[0-9]{15}" maxlength="15" required value="<?= e($sample['chip_number_vet'] ?? '') ?>">
          <div class="field-note">Typicky 15 cislic.</div>
        </div>
        <div>
          <label for="sample_type">Typ vzorku</label>
          <select id="sample_type" name="sample_type" required>
            <?php $selectedType = $sample['sample_type'] ?? 'buccal_swab'; ?>
            <option value="buccal_swab" <?= $selectedType === 'buccal_swab' ? 'selected' : '' ?>>Bukalni ster</option>
            <option value="blood" <?= $selectedType === 'blood' ? 'selected' : '' ?>>Krevni vzorek</option>
            <option value="other" <?= $selectedType === 'other' ? 'selected' : '' ?>>Jine</option>
          </select>
        </div>
        <div>
          <label for="sample_type_other">Jiny typ vzorku</label>
          <input id="sample_type_other" name="sample_type_other" value="<?= e($sample['sample_type_other'] ?? '') ?>">
        </div>
        <div>
          <label for="material_count">Pocet odebranych zkumavek</label>
          <select id="material_count" name="material_count" required>
            <?php $count = (string) ($sample['material_count'] ?? '1'); ?>
            <?php foreach (['1','2','3','4','5','jine'] as $option): ?>
              <option value="<?= e($option) ?>" <?= $count === $option ? 'selected' : '' ?>><?= e($option) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="collection_date">Datum odberu</label>
          <input id="collection_date" name="collection_date" type="date" required value="<?= e($sample['collection_date'] ?? date('Y-m-d')) ?>">
        </div>
      </div>
      <div class="actions">
        <button type="submit">Odeslat odber</button>
      </div>
    </form>
  <?php endif; ?>
</section>
