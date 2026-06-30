<?php
/** @var array<string, mixed> $request */
?>
<div class="card">
    <h1>Hotovo</h1>
    <div class="alert alert--ok">Převzetí psa <strong><?= e($request['dog_name'] ?? '') ?></strong> bylo potvrzeno a vlastnictví převedeno.</div>
    <p>Na váš e-mail jsme poslali odkaz pro <strong>nastavení hesla</strong>. Po přihlášení uvidíte svého psa v portálu.</p>
    <p class="muted">Tuto stránku můžete zavřít.</p>
</div>
