<?php use App\Core\Csrf; ?>
<section>
  <div class="topbar">
    <div>
      <h1>Veterináři</h1>
      <p class="muted">Evidence veterinářů a klinik pro přiřazování odběrových sad.</p>
    </div>
    <a class="button secondary" href="/admin">Zpět na vzorky</a>
  </div>

  <div class="panel">
    <h2>Nový veterinář nebo klinika</h2>
    <form method="post" action="/admin/vets">
      <?= Csrf::field() ?>
      <div class="grid two">
        <div>
          <label for="name">Jméno veterináře / kontaktní osoby</label>
          <input id="name" name="name" required>
        </div>
        <div>
          <label for="clinic_name">Klinika</label>
          <input id="clinic_name" name="clinic_name">
        </div>
        <div>
          <label for="chamber_number">Číslo komory</label>
          <input id="chamber_number" name="chamber_number">
        </div>
        <div>
          <label for="email">E-mail</label>
          <input id="email" name="email" type="email">
        </div>
        <div>
          <label for="phone">Telefon</label>
          <input id="phone" name="phone">
        </div>
        <div>
          <label for="address">Adresa</label>
          <input id="address" name="address">
        </div>
      </div>
      <div class="actions">
        <button type="submit">Uložit veterináře</button>
      </div>
    </form>
  </div>

  <h2>Uložení veterináři</h2>
  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Jméno</th>
        <th>Klinika</th>
        <th>Kontakt</th>
        <th>Adresa</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($vets as $vet): ?>
        <tr>
          <td><?= e($vet['id']) ?></td>
          <td><?= e($vet['name']) ?></td>
          <td><?= e($vet['clinic_name'] ?? '') ?></td>
          <td>
            <?= e($vet['email'] ?? '') ?>
            <?= ($vet['email'] ?? '') && ($vet['phone'] ?? '') ? '<br>' : '' ?>
            <?= e($vet['phone'] ?? '') ?>
          </td>
          <td><?= e($vet['address'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$vets): ?>
        <tr><td colspan="5" class="muted">Zatím není uložen žádný veterinář.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>
