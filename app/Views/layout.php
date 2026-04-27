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
    document.querySelectorAll('[data-step-form]').forEach((form) => {
      const steps = Array.from(form.querySelectorAll('[data-step]'));
      const tabs = Array.from(form.querySelectorAll('[data-step-target]'));
      const previous = form.querySelector('[data-step-prev]');
      const next = form.querySelector('[data-step-next]');
      const submit = form.querySelector('[data-step-submit]');
      let current = 0;

      const show = (index) => {
        current = Math.max(0, Math.min(index, steps.length - 1));
        steps.forEach((step, stepIndex) => {
          step.hidden = stepIndex !== current;
        });
        tabs.forEach((tab, tabIndex) => {
          tab.classList.toggle('active', tabIndex === current);
        });
        if (previous) previous.hidden = current === 0;
        if (next) next.hidden = current === steps.length - 1;
        if (submit) submit.hidden = current !== steps.length - 1;
      };

      const currentStepIsValid = () => {
        const fields = Array.from(steps[current].querySelectorAll('input, select, textarea'));
        for (const field of fields) {
          if (!field.checkValidity()) {
            field.reportValidity();
            return false;
          }
        }
        return true;
      };

      tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
          let target = Number(tab.dataset.stepTarget || 0);
          if (target > current + 1) target = current + 1;
          if (target <= current || currentStepIsValid()) show(target);
        });
      });
      previous?.addEventListener('click', () => show(current - 1));
      next?.addEventListener('click', () => {
        if (currentStepIsValid()) show(current + 1);
      });
      show(0);
    });
  </script>
</body>
</html>
