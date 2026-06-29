<?php
/** @var bool $invalid */
/** @var string $token */
/** @var array<string, mixed>|null $request */
?>
<div class="card">
    <h1>Prevzeti psa</h1>
    <?php if (!empty($invalid)): ?>
        <div class="alert alert--error">Odkaz je neplatny nebo vyprsel. Pozadejte puvodniho majitele o nove zaslani.</div>
    <?php else: ?>
        <p>Pes <span class="sample-code"><?= e($request['dog_name']) ?></span> vam ma byt preveden jako novemu majiteli
            (<?= e($request['new_owner_email']) ?>).</p>
        <p>Potvrzenim prevezmete psa do sve sprava. Nasledne vam prijde e-mail s odkazem pro nastaveni hesla do portalu.</p>
        <form method="post" action="/transfer/<?= e($token) ?>">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn--primary">Potvrdit prevzeti psa</button>
        </form>
    <?php endif; ?>
</div>
