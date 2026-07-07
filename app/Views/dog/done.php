<?php
/** @var array<string, mixed> $sample */
?>
<div class="card">
    <h1><?= t('Děkujeme') ?></h1>
    <div class="alert alert--ok"><?= t('Registrace psa ke vzorku {code} byla odeslána.', [
        'code' => '<span class="sample-code">' . e($sample['sample_id']) . '</span>',
    ]) ?></div>
    <p><?= t('Na zadaný e-mail jsme poslali odkaz pro {action}. Po přihlášení uvidíte svého psa, budete moci doplnit údaje a vyplnit dotazník.', [
        'action' => '<strong>' . t('nastavení hesla') . '</strong>',
    ]) ?></p>
    <p class="muted"><?= t('Tuto stránku můžete zavřít.') ?></p>
</div>
