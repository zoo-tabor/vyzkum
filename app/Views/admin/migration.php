<?php use App\Core\Csrf; ?>
<section>
  <div class="topbar">
    <div>
      <h1>Migrace databaze</h1>
      <p class="muted">Spusteni zakladniho schematu z <code>database/schema.sql</code>.</p>
    </div>
    <a class="button secondary" href="/admin">Zpet na administraci</a>
  </div>

  <?php if ($error): ?>
    <div class="notice danger"><?= e($error) ?></div>
  <?php endif; ?>

  <?php if ($migrated): ?>
    <div class="notice ok">Migrace probehla uspesne.</div>
  <?php endif; ?>

  <div class="panel">
    <h2>Aktualni stav</h2>
    <?php if ($tables): ?>
      <p>Databaze obsahuje tyto tabulky:</p>
      <ul>
        <?php foreach ($tables as $table): ?>
          <li><code><?= e($table) ?></code></li>
        <?php endforeach; ?>
      </ul>
      <p class="muted">Migrace se automaticky nespousti, pokud databaze uz obsahuje nejake tabulky.</p>
    <?php else: ?>
      <p>Databaze zatim neobsahuje zadne tabulky.</p>
      <form method="post" action="/admin/migrate">
        <?= Csrf::field() ?>
        <div class="notice">
          Tato akce vytvori tabulky pro vzorky, psy, majitele, souhlasy, rodokmeny, laboratorni zaznamy a audit log.
        </div>
        <div class="actions">
          <button type="submit">Spustit migraci</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</section>
