<?php
/** @var bool $invalid */
/** @var string $token */
/** @var array<string, mixed>|null $request */
?>
<div class="card">
    <h1><?= t('Převzetí psa') ?></h1>
    <?php if (!empty($invalid)): ?>
        <div class="alert alert--error"><?= t('Odkaz je neplatný nebo vypršel. Požádejte původního majitele o nové zaslání.') ?></div>
    <?php else: ?>
        <p><?= t('Pes {dog} vám má být převeden jako novému majiteli ({email}).', [
            'dog' => '<span class="sample-code">' . e($request['dog_name']) . '</span>',
            'email' => e($request['new_owner_email']),
        ]) ?></p>
        <p><?= t('Potvrzením převezmete psa do své správy. Následně vám přijde e-mail s odkazem pro nastavení hesla do portálu.') ?></p>
        <form method="post" action="/transfer/<?= e($token) ?>">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn--primary"><?= t('Potvrdit převzetí psa') ?></button>
        </form>
    <?php endif; ?>
</div>
