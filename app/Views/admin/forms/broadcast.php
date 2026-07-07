<?php
/** @var array<string, mixed> $def */
/** @var int $livingCount */
/** @var int $livingEmailCount */
/** @var int $allCount */
/** @var int $allEmailCount */
/** @var string $defaultSubject */
/** @var string $defaultBody */
/** @var string|null $error */
$defId = (int) $def['id'];
?>
<div class="page-head">
    <h1><?= t('Rozeslat dotazník') ?> <span class="muted">/ <?= e($def['name']) ?></span></h1>
    <p><a href="/admin/forms/<?= $defId ?>">&larr; <?= t('Zpět na dotazník') ?></a></p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p><?= t('Plemeno: {breed}. Odešle se 1 e-mail na psa (majitelé bez e-mailu se přeskočí).', ['breed' => '<strong>' . e(\App\Support\Breeds::translate($def['breed_name'])) . '</strong>']) ?></p>
    <?php if ($allEmailCount === 0): ?>
        <p class="muted"><?= t('Žádný majitel nemá primární e-mail - není komu rozeslat.') ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <h2><?= t('Text e-mailu') ?></h2>
    <p class="muted">
        <?= t('Můžete použít zástupné značky: {pes} (jméno psa), {majitel} (jméno majitele), {odkaz} (odkaz na vyplnění). Pokud {odkaz} vynecháte, přidá se automaticky na konec.', [
            'pes' => '<code>{pes}</code>',
            'majitel' => '<code>{majitel}</code>',
            'odkaz' => '<code>{odkaz}</code>',
        ]) ?>
    </p>
    <form method="post" action="/admin/forms/<?= $defId ?>/send"
          onsubmit="return confirm(<?= e(json_encode(t('Opravdu rozeslat dotazník vybraným majitelům?'), JSON_UNESCAPED_UNICODE)) ?>);">
        <?= \App\Core\Csrf::field() ?>

        <fieldset style="border:1px solid var(--line); border-radius:8px; padding:1rem; margin-bottom:1rem;">
            <legend><strong><?= t('Komu rozeslat') ?></strong></legend>
            <label class="inline"><input type="radio" name="recipients" value="living" checked>
                <?= t('Jen žijícím psům') ?> <span class="muted">(<?= t('{emails} e-mailů z {dogs} psů', ['emails' => (int) $livingEmailCount, 'dogs' => (int) $livingCount]) ?>)</span></label>
            <br>
            <label class="inline"><input type="radio" name="recipients" value="all">
                <?= t('Všem psům plemene i uhynulým') ?> <span class="muted">(<?= t('{emails} e-mailů z {dogs} psů', ['emails' => (int) $allEmailCount, 'dogs' => (int) $allCount]) ?>)</span></label>
        </fieldset>

        <label for="subject"><?= t('Předmět') ?> *</label>
        <input type="text" id="subject" name="subject" required value="<?= e($defaultSubject) ?>">

        <label for="body"><?= t('Text') ?> *</label>
        <textarea id="body" name="body" rows="12" required><?= e($defaultBody) ?></textarea>

        <p>
            <button type="submit" class="btn btn--primary"<?= $allEmailCount === 0 ? ' disabled' : '' ?>><?= t('Odeslat majitelům') ?></button>
            <a class="btn" href="/admin/forms/<?= $defId ?>"><?= t('Zrušit') ?></a>
        </p>
    </form>
</div>
