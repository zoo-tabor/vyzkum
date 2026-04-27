<section>
  <div class="topbar no-print">
    <div>
      <h1>Tisk štítků</h1>
      <p class="muted">
        <?php if (!empty($batch['id'])): ?>
          Dávka #<?= e($batch['id']) ?><?= !empty($batch['label']) ? ' / ' . e($batch['label']) : '' ?>.
        <?php endif; ?>
        Štítky lze z administrace vytisknout znovu.
      </p>
    </div>
    <div class="actions compact">
      <button type="button" onclick="window.print()">Tisk</button>
      <a class="button secondary" href="/admin/batches">Dávky</a>
      <a class="button secondary" href="/admin">Hotovo</a>
    </div>
  </div>

  <div class="notice no-print">
    QR kódy se generují lokálně v prohlížeči z uložených odkazů. Žádná externí QR služba se při tisku nevolá.
    V tiskovém dialogu použijte formát A4, měřítko 100 % a vypnuté okraje / tisk bez přizpůsobení stránce.
  </div>

  <?php
    $labels = [];
    foreach ($rows as $row) {
        $labels[] = [
            'sample_id' => $row['sample_id'],
            'role' => 'Veterinář',
            'url' => $row['vet_url'] ?? null,
        ];
        $labels[] = [
            'sample_id' => $row['sample_id'],
            'role' => 'Majitel',
            'url' => $row['owner_url'] ?? null,
        ];
    }
  ?>

  <?php foreach (array_chunk($labels, 65) as $pageLabels): ?>
    <div class="label-page">
      <div class="labels">
        <?php foreach ($pageLabels as $label): ?>
          <article class="label-sheet">
            <?php if (!empty($label['url'])): ?>
              <div class="qr-code" data-qr-code="<?= e($label['url']) ?>"></div>
              <div class="label-copy">
                <strong><?= e($label['sample_id']) ?></strong>
                <span><?= e($label['role']) ?></span>
                <small>Výzkum dlouhověkosti psů</small>
              </div>
            <?php else: ?>
              <div class="notice danger">QR odkaz už není dostupný.</div>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>
</section>
