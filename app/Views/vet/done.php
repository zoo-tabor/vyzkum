<?php
/** @var array<string, mixed> $sample */
?>
<div class="card">
    <h1><?= t('Hotovo') ?></h1>
    <div class="alert alert--ok"><?= t('Veterinární část vzorku {code} byla uložena. Odkaz je jednorázový.', [
        'code' => '<span class="sample-code">' . e($sample['sample_id']) . '</span>',
    ]) ?></div>
    <p class="muted"><?= t('Děkujeme. Tuto stránku můžete zavřít.') ?></p>
</div>
