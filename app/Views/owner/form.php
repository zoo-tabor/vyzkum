<?php use App\Core\Csrf; ?>
<section class="panel">
  <h1>Registrace psa</h1>
  <p>Vzorek <span class="sample-code"><?= e($sample['sample_id']) ?></span></p>

  <?php if ($sample['owner_submitted_at'] !== null): ?>
    <div class="notice ok">Majitelsk&aacute; registrace u&zcaron; byla odesl&aacute;na. Odkaz je jednor&aacute;zov&yacute;.</div>
  <?php else: ?>
    <?php if ($errors): ?>
      <div class="notice danger"><?= e(implode(' ', $errors)) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" data-step-form>
      <?= Csrf::field() ?>

      <div class="step-tabs" aria-label="Kroky formulare">
        <button type="button" class="step-tab active" data-step-target="0">Pes</button>
        <button type="button" class="step-tab" data-step-target="1">Stav</button>
        <button type="button" class="step-tab" data-step-target="2">Rodokmen</button>
        <button type="button" class="step-tab" data-step-target="3">Kontakt</button>
        <button type="button" class="step-tab" data-step-target="4">Souhlas</button>
      </div>

      <section class="form-step" data-step>
        <h2>Pes</h2>
        <div class="grid two">
          <div>
            <label for="chip_number">&Ccaron;&iacute;slo &ccaron;ipu psa</label>
            <input id="chip_number" name="chip_number" inputmode="numeric" maxlength="15" pattern="[0-9]{15}" required value="<?= e($sample['chip_number'] ?? $sample['chip_number_vet'] ?? '') ?>" <?= !empty($sample['chip_number_vet']) ? 'readonly' : '' ?>>
          </div>
          <div>
            <label for="dog_name">Jm&eacute;no psa</label>
            <input id="dog_name" name="dog_name" required value="<?= e($sample['dog_name'] ?? '') ?>">
          </div>
          <div>
            <label for="breed">Plemeno</label>
            <input id="breed" name="breed" required value="<?= e($sample['breed'] ?? '') ?>">
          </div>
          <div>
            <label for="sex">Pohlav&iacute;</label>
            <select id="sex" name="sex" required>
              <?php $sex = $sample['sex'] ?? 'unknown'; ?>
              <option value="unknown" <?= $sex === 'unknown' ? 'selected' : '' ?>>Neuvedeno</option>
              <option value="male" <?= $sex === 'male' ? 'selected' : '' ?>>Pes</option>
              <option value="female" <?= $sex === 'female' ? 'selected' : '' ?>>Fena</option>
            </select>
          </div>
          <div>
            <label for="birth_date">Datum narozen&iacute;</label>
            <input id="birth_date" name="birth_date" type="date" required value="<?= e($sample['birth_date'] ?? '') ?>">
          </div>
          <div>
            <label for="pedigree_number">&Ccaron;&iacute;slo z&aacute;pisu / pedigree</label>
            <input id="pedigree_number" name="pedigree_number" required value="<?= e($sample['pedigree_number'] ?? '') ?>">
          </div>
          <div>
            <label for="registry">Registr / federace</label>
            <input id="registry" name="registry" value="<?= e($sample['registry'] ?? '') ?>" placeholder="CMKU, CLP, FCI...">
          </div>
        </div>
      </section>

      <section class="form-step" data-step hidden>
        <h2>Stav psa v dob&ecaron; odb&ecaron;ru</h2>
        <div class="grid">
          <div>
            <label for="health_status">Stav</label>
            <select id="health_status" name="health_status" required>
              <?php $health = $sample['health_status'] ?? ''; ?>
              <option value="">Vyberte</option>
              <option value="healthy" <?= $health === 'healthy' ? 'selected' : '' ?>>Zdrav&yacute;</option>
              <option value="chronic" <?= $health === 'chronic' ? 'selected' : '' ?>>M&aacute; chronick&eacute; onemocn&ecaron;n&iacute;</option>
              <option value="serious_history" <?= $health === 'serious_history' ? 'selected' : '' ?>>Prod&ecaron;lal z&aacute;va&zcaron;n&eacute; onemocn&ecaron;n&iacute;</option>
              <option value="unknown" <?= $health === 'unknown' ? 'selected' : '' ?>>Jin&eacute; / nev&iacute;m</option>
            </select>
          </div>
          <div>
            <label for="health_note">Pozn&aacute;mka ke zdravotn&iacute;mu stavu</label>
            <textarea id="health_note" name="health_note"><?= e($sample['health_note'] ?? '') ?></textarea>
          </div>
        </div>
      </section>

      <section class="form-step" data-step hidden>
        <h2>Rodokmen</h2>
        <div>
          <label for="pedigree">Fotografie nebo sken pr&uring;kazu p&uring;vodu</label>
          <input id="pedigree" name="pedigree" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required>
          <div class="field-note">Podporovan&yacute; je JPG, PNG, WEBP nebo PDF do 10 MB.</div>
        </div>
      </section>

      <section class="form-step" data-step hidden>
        <h2>Kontakt</h2>
        <div class="grid two">
          <div>
            <label for="owner_name">Jm&eacute;no a p&rcaron;&iacute;jmen&iacute; majitele</label>
            <input id="owner_name" name="owner_name" required value="<?= e($sample['owner_name'] ?? '') ?>">
          </div>
          <div>
            <label for="owner_email">E-mail</label>
            <input id="owner_email" name="owner_email" type="email" required value="<?= e($sample['owner_email'] ?? '') ?>">
          </div>
          <div>
            <label for="owner_phone">Telefon</label>
            <input id="owner_phone" name="owner_phone" value="<?= e($sample['owner_phone'] ?? '') ?>">
          </div>
          <div>
            <label for="owner_address">Adresa</label>
            <input id="owner_address" name="owner_address" value="<?= e($sample['owner_address'] ?? '') ?>">
            <div class="field-note">Nepovinn&eacute; pole.</div>
          </div>
        </div>
      </section>

      <section class="form-step" data-step hidden>
        <h2>Souhlas</h2>
        <div class="notice">
          Souhlas&iacute;m se za&rcaron;azen&iacute;m vzorku psa do v&yacute;zkumn&eacute; studie dlouhov&ecaron;kosti, se zpracov&aacute;n&iacute;m osobn&iacute;ch &uacute;daj&uring; a se zpracov&aacute;n&iacute;m genetick&yacute;ch dat psa pro v&yacute;zkumn&eacute; &uacute;&ccaron;ely.
          <details>
            <summary>Zobrazit podm&iacute;nky</summary>
            <p>Data budou pou&zcaron;ita pro v&yacute;zkumnou evidenci vzorku, validaci p&uring;vodu psa, kontaktov&aacute;n&iacute; majitele v souvislosti se studi&iacute; a p&rcaron;&iacute;padn&yacute; budouc&iacute; follow-up. P&rcaron;&iacute;stup k dat&uring;m m&aacute; pouze opr&aacute;vn&ecaron;n&yacute; v&yacute;zkumn&yacute; t&yacute;m.</p>
          </details>
        </div>
        <p><label><input type="checkbox" name="main_consent" value="1" required> Potvrzuji souhlas a opr&aacute;vn&ecaron;n&iacute; psa do studie p&rcaron;ihl&aacute;sit.</label></p>
        <p><label><input type="checkbox" name="future_contact_consent" value="1"> Souhlas&iacute;m s budouc&iacute;m kontaktov&aacute;n&iacute;m kv&uring;li datu a p&rcaron;&iacute;&ccaron;in&ecaron; &uacute;mrt&iacute; psa.</label></p>
        <p><label><input type="checkbox" name="results_consent" value="1"> Chci b&yacute;t informov&aacute;n/a o p&rcaron;&iacute;padn&yacute;ch v&yacute;sledc&iacute;ch.</label></p>
        <p><label><input type="checkbox" name="newsletter_consent" value="1"> Chci dost&aacute;vat novinky k projektu.</label></p>
      </section>

      <div class="actions step-actions">
        <button type="button" class="button secondary" data-step-prev hidden>Zp&ecaron;t</button>
        <button type="button" data-step-next>Pokra&ccaron;ovat</button>
        <button type="submit" data-step-submit hidden>Odeslat registraci</button>
      </div>
    </form>
  <?php endif; ?>
</section>
