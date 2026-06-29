<?php
/** @var array<string, mixed> $request */
?>
<div class="card">
    <h1>Hotovo</h1>
    <div class="alert alert--ok">Prevzeti psa <strong><?= e($request['dog_name'] ?? '') ?></strong> bylo potvrzeno a vlastnictvi prevedeno.</div>
    <p>Na vas e-mail jsme poslali odkaz pro <strong>nastaveni hesla</strong>. Po prihlaseni uvidite sveho psa v portalu.</p>
    <p class="muted">Tuto stranku muzete zavrit.</p>
</div>
