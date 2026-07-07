<?php
/** @var array<string, mixed> $request */
/** @var bool $inviteSent */
?>
<div class="card">
    <h1><?= t('Hotovo') ?></h1>
    <div class="alert alert--ok"><?= t('Převzetí psa {dog} bylo potvrzeno a vlastnictví převedeno.', [
        'dog' => '<strong>' . e($request['dog_name'] ?? '') . '</strong>',
    ]) ?></div>
    <?php if (!empty($inviteSent)): ?>
        <p><?= t('Na váš e-mail jsme poslali odkaz pro {action}. Po přihlášení uvidíte svého psa v portálu.', [
            'action' => '<strong>' . t('nastavení hesla') . '</strong>',
        ]) ?></p>
    <?php else: ?>
        <p><?= t('Ke svému psovi se dostanete po přihlášení do portálu {password}.', [
            'password' => '<strong>' . t('svým stávajícím heslem') . '</strong>',
        ]) ?></p>
    <?php endif; ?>
    <p class="muted"><?= t('Tuto stránku můžete zavřít.') ?></p>
</div>
