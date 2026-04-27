<?php use App\Core\Csrf; ?>
<section>
  <div class="topbar">
    <div>
      <h1>Nova davka vzorku</h1>
      <p class="muted">Vygeneruje sample_id, tokenizovane odkazy a tiskove stitky.</p>
    </div>
    <a class="button secondary" href="/admin">Zpet na vzorky</a>
  </div>

  <form class="panel" method="post" action="/admin/samples/new-batch">
    <?= Csrf::field() ?>
    <div class="grid two">
      <div>
        <label for="count">Pocet sad</label>
        <input id="count" name="count" type="number" min="1" max="200" value="20" required>
        <div class="field-note">Maximum jedne davky je 200 sad.</div>
      </div>
      <div>
        <label for="vet_id">Prirazeni veterinari / klinice</label>
        <select id="vet_id" name="vet_id">
          <option value="">Bez prirazeni</option>
          <?php foreach ($vets as $vet): ?>
            <option value="<?= e($vet['id']) ?>">
              <?= e(trim($vet['name'] . (($vet['clinic_name'] ?? '') ? ' / ' . $vet['clinic_name'] : ''))) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="notice">
      QR odkazy se z bezpecnostnich duvodu zobrazi jen hned po vygenerovani. Pozdeji uz z databaze nelze zpetne ziskat puvodni tokeny.
    </div>
    <div class="actions">
      <button type="submit">Vygenerovat davku</button>
      <a class="button secondary" href="/admin/vets">Spravovat veterinare</a>
    </div>
  </form>
</section>
