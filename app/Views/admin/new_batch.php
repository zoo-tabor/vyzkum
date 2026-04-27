<?php use App\Core\Csrf; ?>
<section>
  <div class="topbar">
    <div>
      <h1>Nová dávka vzorků</h1>
      <p class="muted">Vygeneruje sample_id, tokenizované odkazy a tiskové štítky.</p>
    </div>
    <a class="button secondary" href="/admin">Zpět na vzorky</a>
  </div>

  <form class="panel" method="post" action="/admin/samples/new-batch">
    <?= Csrf::field() ?>
    <div class="grid two">
      <div>
        <label for="count">Počet sad</label>
        <input id="count" name="count" type="number" min="1" max="200" value="20" required>
        <div class="field-note">Maximum jedné dávky je 200 sad.</div>
      </div>
      <div>
        <label for="vet_id">Přiřazení veterináři / klinice</label>
        <select id="vet_id" name="vet_id">
          <option value="">Bez přiřazení</option>
          <?php foreach ($vets as $vet): ?>
            <option value="<?= e($vet['id']) ?>">
              <?= e(trim($vet['name'] . (($vet['clinic_name'] ?? '') ? ' / ' . $vet['clinic_name'] : ''))) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="label">Popis dávky</label>
        <input id="label" name="label" placeholder="např. duben 2026 / klinika Praha">
      </div>
    </div>
    <div class="notice">
      QR odkazy se uloží k dávce, aby štítky šly později znovu vytisknout.
    </div>
    <div class="actions">
      <button type="submit">Vygenerovat dávku</button>
      <a class="button secondary" href="/admin/vets">Spravovat veterináře</a>
    </div>
  </form>
</section>
