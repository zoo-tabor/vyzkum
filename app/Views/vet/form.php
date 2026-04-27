<?php use App\Core\Csrf; ?>
<section class="panel vet-panel">
  <h1>Veterin&aacute;rn&iacute; potvrzen&iacute; odb&ecaron;ru</h1>
  <p>Vzorek <span class="sample-code"><?= e($sample['sample_id']) ?></span></p>
  <p class="muted">
    Veterin&aacute;&rcaron;:
    <?= e(trim(($sample['vet_name'] ?? '') . ' ' . (($sample['clinic_name'] ?? '') ? '/ ' . $sample['clinic_name'] : '')) ?: 'předvyplní administrace') ?>
  </p>

  <?php if ($sample['vet_submitted_at'] !== null): ?>
    <div class="notice ok">Veterin&aacute;rn&iacute; &ccaron;&aacute;st u&zcaron; byla ulo&zcaron;ena. Odkaz je jednor&aacute;zov&yacute;.</div>
  <?php else: ?>
    <?php if ($errors): ?>
      <div class="notice danger"><?= e(implode(' ', $errors)) ?></div>
    <?php endif; ?>

    <form method="post" class="compact-form">
      <?= Csrf::field() ?>
      <div class="grid">
        <div>
          <label for="chip_number_vet">&Ccaron;&iacute;slo &ccaron;ipu psa</label>
          <input id="chip_number_vet" name="chip_number_vet" inputmode="numeric" pattern="[0-9]{15}" maxlength="15" required value="<?= e($sample['chip_number_vet'] ?? '') ?>">
          <div class="field-note">Typicky 15 &ccaron;&iacute;slic.</div>
        </div>
        <div>
          <label for="sample_type">Typ vzorku</label>
          <select id="sample_type" name="sample_type" required>
            <?php $selectedType = $sample['sample_type'] ?? 'buccal_swab'; ?>
            <option value="buccal_swab" <?= $selectedType === 'buccal_swab' ? 'selected' : '' ?>>Buk&aacute;ln&iacute; st&ecaron;r</option>
            <option value="blood" <?= $selectedType === 'blood' ? 'selected' : '' ?>>Krevn&iacute; vzorek</option>
            <option value="other" <?= $selectedType === 'other' ? 'selected' : '' ?>>Jin&eacute;</option>
          </select>
        </div>
        <div>
          <label for="sample_type_other">Jin&yacute; typ vzorku</label>
          <input id="sample_type_other" name="sample_type_other" value="<?= e($sample['sample_type_other'] ?? '') ?>">
        </div>
        <div>
          <label for="material_count">Po&ccaron;et odebran&yacute;ch zkumavek</label>
          <select id="material_count" name="material_count" required>
            <?php $count = (string) ($sample['material_count'] ?? '1'); ?>
            <?php foreach (['1','2','3','4','5','jiné'] as $option): ?>
              <option value="<?= e($option) ?>" <?= $count === $option ? 'selected' : '' ?>><?= e($option) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="collection_date">Datum odb&ecaron;ru</label>
          <input id="collection_date" name="collection_date" type="date" required value="<?= e($sample['collection_date'] ?? date('Y-m-d')) ?>">
        </div>
      </div>
      <div class="actions">
        <button type="submit">Odeslat odb&ecaron;r</button>
      </div>
    </form>
  <?php endif; ?>
</section>
