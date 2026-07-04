<?php
/** @var array<string, mixed> $request */
/** @var bool $inviteSent */
?>
<div class="card">
    <h1>Hotovo</h1>
    <div class="alert alert--ok">Převzetí psa <strong><?= e($request['dog_name'] ?? '') ?></strong> bylo potvrzeno a vlastnictví převedeno.</div>
    <?php if (!empty($inviteSent)): ?>
        <p>Na váš e-mail jsme poslali odkaz pro <strong>nastavení hesla</strong>. Po přihlášení uvidíte svého psa v portálu.</p>
    <?php else: ?>
        <p>Ke svému psovi se dostanete po přihlášení do portálu <strong>svým stávajícím heslem</strong>.</p>
    <?php endif; ?>
    <p class="muted">Tuto stránku můžete zavřít.</p>
</div>
