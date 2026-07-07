<?php
/** @var array<string, mixed> $tpl */
/** @var array<int, string> $targetLocales */
/** @var array<string, string> $subjectTx */
/** @var array<string, string> $bodyTx */
/** @var string|null $error */

use App\Support\I18n;

$key = (string) $tpl['key'];
$label = static fn (string $k): string => match ($k) {
    'set_password' => t('Nastavení hesla (pozvánka)'),
    'password_reset' => t('Obnova hesla'),
    'ownership_transfer' => t('Převzetí psa (převod vlastnictví)'),
    'form_broadcast' => t('Rozeslání dotazníku'),
    default => $k,
};
?>
<div class="page-head">
    <h1><?= t('Šablona e-mailu:') ?> <?= e($label($key)) ?></h1>
    <p><a href="/admin/email-templates">&larr; <?= t('Zpět na šablony') ?></a></p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <?php if (!empty($tpl['placeholders'])): ?>
        <p class="muted"><?= t('Zástupné značky (nechte beze změny i v překladech):') ?> <code><?= e($tpl['placeholders']) ?></code></p>
    <?php endif; ?>
    <p class="muted"><?= t('Prázdný překlad = použije se český zdroj.') ?></p>

    <form method="post" action="/admin/email-templates/<?= e(rawurlencode($key)) ?>">
        <?= \App\Core\Csrf::field() ?>

        <fieldset style="border:1px solid var(--line); border-radius:8px; padding:1rem; margin-bottom:1rem;">
            <legend><strong><?= e(I18n::name(I18n::defaultLocale())) ?> <?= t('(zdroj)') ?></strong></legend>
            <label for="subject"><?= t('Předmět') ?> *</label>
            <input type="text" id="subject" name="subject" required value="<?= e($tpl['subject']) ?>">
            <label for="body"><?= t('Text') ?> *</label>
            <textarea id="body" name="body" rows="12" required><?= e($tpl['body']) ?></textarea>
        </fieldset>

        <?php foreach ($targetLocales as $code): ?>
            <fieldset style="border:1px solid var(--line); border-radius:8px; padding:1rem; margin-bottom:1rem;">
                <legend>
                    <img src="<?= e(asset('assets/flags/' . I18n::flag($code) . '.svg')) ?>" alt="" width="20" height="15" style="vertical-align:-2px;margin-right:.35rem">
                    <strong><?= e(I18n::name($code)) ?></strong>
                </legend>
                <label for="subject_<?= e($code) ?>"><?= t('Předmět') ?></label>
                <input type="text" id="subject_<?= e($code) ?>" name="subject_tr[<?= e($code) ?>]" value="<?= e($subjectTx[$code] ?? '') ?>">
                <label for="body_<?= e($code) ?>"><?= t('Text') ?></label>
                <textarea id="body_<?= e($code) ?>" name="body_tr[<?= e($code) ?>]" rows="12"><?= e($bodyTx[$code] ?? '') ?></textarea>
            </fieldset>
        <?php endforeach; ?>

        <button type="submit" class="btn btn--primary"><?= t('Uložit šablonu') ?></button>
        <a class="btn" href="/admin/email-templates"><?= t('Zrušit') ?></a>
    </form>
</div>
