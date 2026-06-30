<?php
/** @var bool $invalid */
/** @var string $token */
/** @var array<string, mixed>|null $request */
?>
<div class="card">
    <h1>Převzetí psa</h1>
    <?php if (!empty($invalid)): ?>
        <div class="alert alert--error">Odkaz je neplatný nebo vypršel. Požádejte původního majitele o nové zaslání.</div>
    <?php else: ?>
        <p>Pes <span class="sample-code"><?= e($request['dog_name']) ?></span> vám má být převeden jako novému majiteli
            (<?= e($request['new_owner_email']) ?>).</p>
        <p>Potvrzením převezmete psa do své správy. Následně vám přijde e-mail s odkazem pro nastavení hesla do portálu.</p>
        <form method="post" action="/transfer/<?= e($token) ?>">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn--primary">Potvrdit převzetí psa</button>
        </form>
    <?php endif; ?>
</div>
