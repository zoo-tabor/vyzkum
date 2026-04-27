<section>
  <div class="topbar">
    <div>
      <h1>Vzorky</h1>
      <p class="muted">Přehled posledních záznamů.</p>
    </div>
    <div class="actions compact">
      <a class="button secondary" href="/admin/vets">Veterináři</a>
      <a class="button secondary" href="/admin/migrate">Migrace DB</a>
      <a class="button secondary" href="/admin/samples/new-batch">Nová dávka</a>
      <a class="button" href="/admin/export/samples.csv">Export CSV</a>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Vzorek</th>
        <th>Stav</th>
        <th>Čip</th>
        <th>Pes</th>
        <th>Majitel</th>
        <th>Odběr</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($samples as $sample): ?>
        <tr>
          <td><a href="/admin/samples/<?= e(rawurlencode($sample['sample_id'])) ?>"><?= e($sample['sample_id']) ?></a></td>
          <td><span class="status"><?= e($sample['status']) ?></span></td>
          <td><?= e($sample['chip_number_vet'] ?? '') ?></td>
          <td><?= e(trim(($sample['dog_name'] ?? '') . ' ' . (($sample['breed'] ?? '') ? '/ ' . $sample['breed'] : ''))) ?></td>
          <td><?= e($sample['owner_email'] ?? '') ?></td>
          <td><?= e($sample['collection_date'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
