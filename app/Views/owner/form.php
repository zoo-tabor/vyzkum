<?php use App\Core\Csrf; ?>
<section class="panel">
  <h1>Registrace psa</h1>
  <p>Vzorek <span class="sample-code"><?= e($sample['sample_id']) ?></span></p>

  <div class="progress" aria-hidden="true">
    <span class="step active"></span><span class="step active"></span><span class="step active"></span><span class="step active"></span><span class="step active"></span>
  </div>

  <?php if ($sample['owner_submitted_at'] !== null): ?>
    <div class="notice ok">Majitelska registrace uz byla odeslana. Odkaz je jednorazovy.</div>
  <?php else: ?>
    <?php if ($errors): ?>
      <div class="notice danger"><?= e(implode(' ', $errors)) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <?= Csrf::field() ?>

      <h2>Pes</h2>
      <div class="grid two">
        <div>
          <label for="chip_number">Cislo cipu psa</label>
          <input id="chip_number" name="chip_number" inputmode="numeric" maxlength="15" pattern="[0-9]{15}" required value="<?= e($sample['chip_number'] ?? $sample['chip_number_vet'] ?? '') ?>" <?= !empty($sample['chip_number_vet']) ? 'readonly' : '' ?>>
        </div>
        <div>
          <label for="dog_name">Jmeno psa</label>
          <input id="dog_name" name="dog_name" required value="<?= e($sample['dog_name'] ?? '') ?>">
        </div>
        <div>
          <label for="breed">Plemeno</label>
          <input id="breed" name="breed" required value="<?= e($sample['breed'] ?? '') ?>">
        </div>
        <div>
          <label for="sex">Pohlavi</label>
          <select id="sex" name="sex" required>
            <?php $sex = $sample['sex'] ?? 'unknown'; ?>
            <option value="unknown" <?= $sex === 'unknown' ? 'selected' : '' ?>>Neuvedeno</option>
            <option value="male" <?= $sex === 'male' ? 'selected' : '' ?>>Pes</option>
            <option value="female" <?= $sex === 'female' ? 'selected' : '' ?>>Fena</option>
          </select>
        </div>
        <div>
          <label for="birth_date">Datum narozeni</label>
          <input id="birth_date" name="birth_date" type="date" required value="<?= e($sample['birth_date'] ?? '') ?>">
        </div>
        <div>
          <label for="pedigree_number">Cislo zapisu / pedigree</label>
          <input id="pedigree_number" name="pedigree_number" required value="<?= e($sample['pedigree_number'] ?? '') ?>">
        </div>
        <div>
          <label for="registry">Registr / federace</label>
          <input id="registry" name="registry" value="<?= e($sample['registry'] ?? '') ?>" placeholder="CMKU, CLP, FCI...">
        </div>
      </div>

      <h2>Stav psa v dobe odberu</h2>
      <div class="grid">
        <div>
          <label for="health_status">Stav</label>
          <select id="health_status" name="health_status" required>
            <?php $health = $sample['health_status'] ?? ''; ?>
            <option value="">Vyberte</option>
            <option value="healthy" <?= $health === 'healthy' ? 'selected' : '' ?>>Zdravy</option>
            <option value="chronic" <?= $health === 'chronic' ? 'selected' : '' ?>>Ma chronicke onemocneni</option>
            <option value="serious_history" <?= $health === 'serious_history' ? 'selected' : '' ?>>Prodelal zavazne onemocneni</option>
            <option value="unknown" <?= $health === 'unknown' ? 'selected' : '' ?>>Jine / nevim</option>
          </select>
        </div>
        <div>
          <label for="health_note">Poznamka ke zdravotnimu stavu</label>
          <textarea id="health_note" name="health_note"><?= e($sample['health_note'] ?? '') ?></textarea>
        </div>
      </div>

      <h2>Rodokmen</h2>
      <div>
        <label for="pedigree">Fotografie nebo sken prukazu puvodu</label>
        <input id="pedigree" name="pedigree" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required>
        <div class="field-note">Podporovany je JPG, PNG, WEBP nebo PDF do 10 MB.</div>
      </div>

      <h2>Kontakt</h2>
      <div class="grid two">
        <div>
          <label for="owner_name">Jmeno a prijmeni majitele</label>
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
      </div>

      <h2>Souhlas</h2>
      <div class="notice">
        Souhlasim se zarazenim vzorku psa do vyzkumne studie dlouhovekosti, se zpracovanim osobnich udaju a se zpracovanim genetickych dat psa pro vyzkumne ucely.
        <details>
          <summary>Zobrazit podminky</summary>
          <p>Data budou pouzita pro vyzkumnou evidenci vzorku, validaci puvodu psa, kontaktovani majitele v souvislosti se studii a pripadny budouci follow-up. Pristup k datum ma pouze opravneny vyzkumny tym.</p>
        </details>
      </div>
      <p><label><input type="checkbox" name="main_consent" value="1" required> Potvrzuji souhlas a opravneni psa do studie prihlasit.</label></p>
      <p><label><input type="checkbox" name="future_contact_consent" value="1"> Souhlasim s budoucim kontaktovanim kvuli datu a pricine umrti psa.</label></p>
      <p><label><input type="checkbox" name="results_consent" value="1"> Chci byt informovan/a o pripadnych vysledcich.</label></p>
      <p><label><input type="checkbox" name="newsletter_consent" value="1"> Chci dostavat novinky k projektu.</label></p>

      <div class="actions">
        <button type="submit">Odeslat registraci</button>
      </div>
    </form>
  <?php endif; ?>
</section>
