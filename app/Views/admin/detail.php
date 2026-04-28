<?php use App\Core\Csrf; ?>
<section class="panel">
  <h1>Vzorek <?= e($sample['sample_id']) ?></h1>
  <p><span class="status"><?= e($sample['status']) ?></span></p>

  <form method="post" action="/admin/samples/<?= e(rawurlencode($sample['sample_id'])) ?>/status">
    <?= Csrf::field() ?>
    <div class="grid two">
      <div>
        <label for="status">Změnit stav</label>
        <select id="status" name="status">
          <?php foreach (['created','assigned_to_vet','vet_submitted','owner_submitted','sample_received','pedigree_checked','data_validated','excluded','analysis_ready','analysis_done','result_available','followup_needed','deceased_reported'] as $status): ?>
            <option value="<?= e($status) ?>" <?= $sample['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="actions">
      <button type="submit">Uložit stav</button>
      <a class="button secondary" href="/admin">Zpět</a>
    </div>
  </form>

  <h2>Veterinární část</h2>
  <table>
    <tr><th>Veterinář</th><td><?= e(trim(($sample['vet_name'] ?? '') . ' ' . (($sample['clinic_name'] ?? '') ? '/ ' . $sample['clinic_name'] : ''))) ?></td></tr>
    <tr><th>Čip zadaný veterinářem</th><td><?= e($sample['chip_number_vet'] ?? '') ?></td></tr>
    <tr><th>Typ vzorku</th><td><?= e($sample['sample_type'] ?? '') ?></td></tr>
    <tr><th>Počet materiálu</th><td><?= e($sample['material_count'] ?? '') ?></td></tr>
    <tr><th>Datum odběru</th><td><?= e($sample['collection_date'] ?? '') ?></td></tr>
  </table>

  <h2>Pes a majitel</h2>
  <table>
    <tr><th>Pes</th><td><?= e($sample['dog_name'] ?? '') ?></td></tr>
    <tr><th>Čip psa</th><td><?= e($sample['dog_chip_number'] ?? '') ?></td></tr>
    <tr><th>Plemeno</th><td><?= e($sample['breed'] ?? '') ?></td></tr>
    <tr><th>Narození</th><td><?= e($sample['birth_date'] ?? '') ?></td></tr>
    <tr><th>Pedigree</th><td><?= e($sample['pedigree_number'] ?? '') ?></td></tr>
    <tr>
      <th>Majitel</th>
      <td>
        <?= e($sample['owner_name'] ?? '') ?><br>
        <?= e($sample['owner_email'] ?? '') ?><?= ($sample['owner_email'] ?? '') && ($sample['owner_phone'] ?? '') ? ', ' : '' ?><?= e($sample['owner_phone'] ?? '') ?>
        <?php if (!empty($sample['owner_address'])): ?>
          <br><?= e($sample['owner_address']) ?>
        <?php endif; ?>
      </td>
    </tr>
    <tr><th>Stav při odběru</th><td><?= e($sample['health_status_at_collection'] ?? '') ?></td></tr>
    <tr><th>Poznámka</th><td><?= e($sample['health_note'] ?? '') ?></td></tr>
  </table>
</section>
