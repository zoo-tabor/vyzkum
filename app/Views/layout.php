<?php use App\Core\Csrf; ?>
<!doctype html>
<html lang="cs">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#146c5f">
  <link rel="manifest" href="<?= e(asset('manifest.webmanifest')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
  <title><?= e($title ?? 'Evidence vzorků psů') ?></title>
</head>
<body>
  <main class="<?= !empty($admin) ? 'admin-shell' : 'shell' ?>">
    <div class="topbar">
      <div class="brand">Studie dlouhověkosti psů</div>
      <?php if (!empty($admin)): ?>
        <a href="/admin">Administrace</a>
      <?php endif; ?>
    </div>
    <?= $content ?>
  </main>
  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('<?= e(asset('sw.js')) ?>').catch(() => {});
    }
  </script>
</body>
</html>
