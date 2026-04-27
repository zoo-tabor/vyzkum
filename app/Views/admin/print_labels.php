<section>
  <div class="topbar no-print">
    <div>
      <h1>Tisk stitku</h1>
      <p class="muted">Vytisknete nebo ulozte PDF hned ted. Tokeny uz pozdeji nebude mozne znovu zobrazit.</p>
    </div>
    <div class="actions compact">
      <button type="button" onclick="window.print()">Tisk</button>
      <a class="button secondary" href="/admin">Hotovo</a>
    </div>
  </div>

  <div class="notice no-print">
    QR obrazky se nacitaji z verejne sluzby api.qrserver.com. Odkazy obsahuji pouze sample_id a nahodne tokeny; pro produkci bez externi zavislosti lze pozdeji doplnit lokalni QR knihovnu.
  </div>

  <div class="labels">
    <?php foreach ($rows as $row): ?>
      <article class="label-sheet">
        <header>
          <strong><?= e($row['sample_id']) ?></strong>
          <span>GWAS dlouhovekost psu</span>
        </header>
        <div class="qr-grid">
          <div class="qr-box">
            <img alt="QR veterinar" src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&amp;data=<?= e(rawurlencode($row['vet_url'])) ?>">
            <strong>Veterinar</strong>
            <small><?= e($row['vet_url']) ?></small>
          </div>
          <div class="qr-box">
            <img alt="QR majitel" src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&amp;data=<?= e(rawurlencode($row['owner_url'])) ?>">
            <strong>Majitel</strong>
            <small><?= e($row['owner_url']) ?></small>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
