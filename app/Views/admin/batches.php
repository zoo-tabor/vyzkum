<section>
  <div class="topbar">
    <div>
      <h1>Dávky vzorků</h1>
      <p class="muted">Archiv vytvořených sad a opakovaný tisk QR štítků.</p>
    </div>
    <div class="actions compact">
      <a class="button secondary" href="/admin">Zpět na vzorky</a>
      <a class="button" href="/admin/samples/new-batch">Nová dávka</a>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Popis</th>
        <th>Veterinář / klinika</th>
        <th>Počet</th>
        <th>Vytvořeno</th>
        <th>Akce</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($batches as $batch): ?>
        <tr>
          <td>#<?= e($batch['id']) ?></td>
          <td><?= e($batch['label'] ?? '') ?></td>
          <td><?= e(trim(($batch['vet_name'] ?? '') . (($batch['clinic_name'] ?? '') ? ' / ' . $batch['clinic_name'] : ''))) ?></td>
          <td><?= e($batch['sample_count']) ?></td>
          <td><?= e($batch['created_at']) ?></td>
          <td><a class="button secondary compact-button" href="/admin/batches/<?= e($batch['id']) ?>/labels">Štítky</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$batches): ?>
        <tr><td colspan="6" class="muted">Zatím není vytvořena žádná dávka.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>
