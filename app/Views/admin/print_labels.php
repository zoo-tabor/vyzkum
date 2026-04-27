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
  </div>

  <div class="labels">
    <?php foreach ($rows as $row): ?>
      <article class="label-sheet">
        <header>
          <strong><?= e($row['sample_id']) ?></strong>
          <span>GWAS dlouhověkost psů</span>
        </header>
        <div class="qr-grid">
          <div class="qr-box">
            <?php if (!empty($row['vet_url'])): ?>
              <div class="qr-code" data-qr-code="<?= e($row['vet_url']) ?>"></div>
              <strong>Veterinář</strong>
              <small><?= e($row['vet_url']) ?></small>
            <?php else: ?>
              <div class="notice danger">QR odkaz už není dostupný.</div>
            <?php endif; ?>
          </div>
          <div class="qr-box">
            <?php if (!empty($row['owner_url'])): ?>
              <div class="qr-code" data-qr-code="<?= e($row['owner_url']) ?>"></div>
              <strong>Majitel</strong>
              <small><?= e($row['owner_url']) ?></small>
            <?php else: ?>
              <div class="notice danger">QR odkaz už není dostupný.</div>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
