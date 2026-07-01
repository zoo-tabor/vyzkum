<?php
/** @var array<string, mixed> $def */
/** @var int $recipientCount */
/** @var int $emailCount */
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
    <p>
        Plemeno: <strong><?= e($def['breed_name']) ?></strong>.
        Žijících psů s majitelem: <strong><?= (int) $recipientCount ?></strong>,
        z toho s e-mailem: <strong><?= (int) $emailCount ?></strong>
        (odešle se <?= (int) $emailCount ?> e-mailů, 1 na psa).
    </p>
    <?php if ($emailCount === 0): ?>
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
          onsubmit="return confirm('Opravdu rozeslat dotazník na <?= (int) $emailCount ?> e-mailů?');">
        <?= \App\Core\Csrf::field() ?>

        <label for="subject">Předmět *</label>
        <input type="text" id="subject" name="subject" required value="<?= e($defaultSubject) ?>">

        <label for="body">Text *</label>
        <textarea id="body" name="body" rows="12" required><?= e($defaultBody) ?></textarea>

        <p>
            <button type="submit" class="btn btn--primary"<?= $emailCount === 0 ? ' disabled' : '' ?>>Odeslat majitelům</button>
            <a class="btn" href="/admin/forms/<?= $defId ?>">Zrušit</a>
        </p>
    </form>
</div>
