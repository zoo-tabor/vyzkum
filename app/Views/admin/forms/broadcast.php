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
    <h1>Rozeslat dotazník <span class="muted">/ <?= e($def['name']) ?></span></h1>
    <p><a href="/admin/forms/<?= $defId ?>">&larr; Zpět na dotazník</a></p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p>Plemeno: <strong><?= e($def['breed_name']) ?></strong>. Odešle se 1 e-mail na psa (majitelé bez e-mailu se přeskočí).</p>
    <?php if ($allEmailCount === 0): ?>
        <p class="muted">Žádný majitel nemá primární e-mail - není komu rozeslat.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Text e-mailu</h2>
    <p class="muted">
        Můžete použít zástupné značky: <code>{pes}</code> (jméno psa),
        <code>{majitel}</code> (jméno majitele), <code>{odkaz}</code> (odkaz na vyplnění).
        Pokud <code>{odkaz}</code> vynecháte, přidá se automaticky na konec.
    </p>
    <form method="post" action="/admin/forms/<?= $defId ?>/send"
          onsubmit="return confirm('Opravdu rozeslat dotazník vybraným majitelům?');">
        <?= \App\Core\Csrf::field() ?>

        <fieldset style="border:1px solid var(--line); border-radius:8px; padding:1rem; margin-bottom:1rem;">
            <legend><strong>Komu rozeslat</strong></legend>
            <label class="inline"><input type="radio" name="recipients" value="living" checked>
                Jen žijícím psům <span class="muted">(<?= (int) $livingEmailCount ?> e-mailů z <?= (int) $livingCount ?> psů)</span></label>
            <br>
            <label class="inline"><input type="radio" name="recipients" value="all">
                Všem psům plemene i uhynulým <span class="muted">(<?= (int) $allEmailCount ?> e-mailů z <?= (int) $allCount ?> psů)</span></label>
        </fieldset>

        <label for="subject">Předmět *</label>
        <input type="text" id="subject" name="subject" required value="<?= e($defaultSubject) ?>">

        <label for="body">Text *</label>
        <textarea id="body" name="body" rows="12" required><?= e($defaultBody) ?></textarea>

        <p>
            <button type="submit" class="btn btn--primary"<?= $allEmailCount === 0 ? ' disabled' : '' ?>>Odeslat majitelům</button>
            <a class="btn" href="/admin/forms/<?= $defId ?>">Zrušit</a>
        </p>
    </form>
</div>
