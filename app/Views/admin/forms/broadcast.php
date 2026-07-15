<?php
/** @var array<string, mixed> $def */
/** @var int $livingCount */
/** @var int $livingEmailCount */
/** @var int $allCount */
/** @var int $allEmailCount */
/** @var array<int, array{owner_id:int, owner_name:string}> $ownersForBreed */
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
        <?= t('Použije se šablona {template}, každý majitel dostane e-mail ve svém jazyce (dle nastaveného jazyka, jinak česky).', [
            'template' => '<a href="/admin/email-templates/form_broadcast"><strong>' . t('Rozeslání dotazníku') . '</strong></a>',
        ]) ?>
        <?= t('Text šablony (vč. překladů) upravíte v Nastavení → Šablony e-mailů.') ?>
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
            <br>
            <label class="inline"><input type="radio" name="recipients" value="owner" id="rcpt-owner">
                <?= t('Konkrétnímu majiteli') ?></label>
            <div style="margin:.5rem 0 0 1.6rem; max-width:360px;">
                <input type="text" id="owner_search" list="broadcast-owners" data-idsync="owner_id" data-idattr="id"
                       placeholder="<?= e(t('začněte psát jméno...')) ?>" autocomplete="off"
                       onfocus="var r=document.getElementById('rcpt-owner'); if(r){r.checked=true;}">
                <input type="hidden" name="owner_id" id="owner_id" value="">
                <datalist id="broadcast-owners">
                    <?php foreach ($ownersForBreed as $o): ?>
                        <option value="<?= e($o['owner_name']) ?>" data-id="<?= (int) $o['owner_id'] ?>"></option>
                    <?php endforeach; ?>
                </datalist>
                <p class="muted" style="margin:.35rem 0 0"><?= t('Odešle se pro všechny psy tohoto plemene daného majitele (i uhynulé).') ?></p>
            </div>
        </fieldset>

        <p>
            <button type="submit" class="btn btn--primary"<?= $allEmailCount === 0 ? ' disabled' : '' ?>><?= t('Odeslat majitelům') ?></button>
            <a class="btn" href="/admin/forms/<?= $defId ?>"><?= t('Zrušit') ?></a>
        </p>
    </form>
</div>
<script src="<?= e(asset('assets/datalist-id.js')) ?>"></script>
