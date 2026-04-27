<?php use App\Core\Csrf; ?>
<section>
  <div class="topbar">
    <div>
      <h1>Migrace databáze</h1>
      <p class="muted">Spuštění základního schématu z <code>database/schema.sql</code>.</p>
    </div>
    <a class="button secondary" href="/admin">Zpět na administraci</a>
  </div>

  <?php if ($error): ?>
    <div class="notice danger"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if ($migrated): ?>
    <div class="notice ok">Migrace proběhla úspěšně.</div>
  <?php endif; ?>

  <div class="panel">
    <h2>Aktuální stav</h2>
    <?php if ($tables): ?>
      <p>Databáze obsahuje tyto tabulky:</p>
      <ul>
        <?php foreach ($tables as $table): ?>
          <li><code><?= e($table) ?></code></li>
        <?php endforeach; ?>
      </ul>
      <p class="muted">Migrace se automaticky nespouští, pokud databáze už obsahuje nějaké tabulky.</p>
    <?php else: ?>
      <p>Databáze zatím neobsahuje žádné tabulky.</p>
      <form method="post" action="/admin/migrate">
        <?= Csrf::field() ?>
        <div class="notice">
          Tato akce vytvoří tabulky pro vzorky, psy, majitele, souhlasy, rodokmeny, laboratorní záznamy a audit log.
        </div>
        <div class="actions">
          <button type="submit">Spustit migraci</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</section>
