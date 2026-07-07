<?php
/** @var array<string, mixed> $def */
/** @var array<string, mixed>|null $editing */
/** @var array<int, array<string, mixed>> $questions */
/** @var array<int, array<int, array<string, mixed>>> $options */
/** @var array<int, string> $targetLocales */
/** @var string $lang */
/** @var array<string, string> $defTx */
/** @var array<int, array<string, string>> $qTx */
/** @var array<int, array<string, string>> $oTx */
/** @var string|null $notice */
/** @var string|null $error */

use App\Support\I18n;

$defId = (int) $def['id'];
?>
<div class="page-head">
    <h1><?= t('Překlady:') ?> <?= e($def['name']) ?></h1>
    <p><a href="/admin/forms/<?= $defId ?>">&larr; <?= t('Zpět na dotazník') ?></a></p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p class="muted">
        <?= t('Vlevo je český zdroj, vpravo doplňte překlad. Prázdné pole = zobrazí se čeština. Klíče otázek/možností se nepřekládají.') ?>
        <?php if ($editing !== null): ?>
            <?= t('Upravovaná verze: {version}.', ['version' => '<strong>v' . (int) $editing['version'] . '</strong>']) ?>
        <?php endif; ?>
    </p>

    <div class="lang-tabs" style="margin-bottom:1rem">
        <?php foreach ($targetLocales as $code): ?>
            <a class="btn <?= $code === $lang ? 'btn--primary' : 'btn--ghost' ?>"
               href="/admin/forms/<?= $defId ?>/translations?lang=<?= e($code) ?>">
                <img src="<?= e(asset('assets/flags/' . I18n::flag($code) . '.svg')) ?>" alt="" width="20" height="15" style="vertical-align:-2px;margin-right:.35rem">
                <?= e(I18n::name($code)) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <form method="post" action="/admin/forms/<?= $defId ?>/translations">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="lang" value="<?= e($lang) ?>">

        <table class="table">
            <thead><tr>
                <th style="width:45%"><?= t('Český zdroj') ?></th>
                <th><?= t('Překlad') ?> (<?= e(I18n::name($lang)) ?>)</th>
            </tr></thead>
            <tbody>
                <tr>
                    <td><strong><?= e($def['name']) ?></strong><br><span class="muted"><?= t('název dotazníku') ?></span></td>
                    <td><input type="text" name="def_name" value="<?= e($defTx['name'] ?? '') ?>"></td>
                </tr>
                <?php if (trim((string) ($def['description'] ?? '')) !== ''): ?>
                    <tr>
                        <td><?= nl2br(e((string) $def['description'])) ?><br><span class="muted"><?= t('popis dotazníku') ?></span></td>
                        <td><textarea name="def_description" rows="2"><?= e($defTx['description'] ?? '') ?></textarea></td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($questions as $q): ?>
                    <?php $qid = (int) $q['id']; ?>
                    <tr>
                        <td colspan="2" style="background:#f6f7f9">
                            <strong><?= t('Otázka:') ?></strong> <code><?= e($q['question_key']) ?></code>
                        </td>
                    </tr>
                    <tr>
                        <td><?= e($q['label']) ?><br><span class="muted"><?= t('text otázky') ?></span></td>
                        <td><input type="text" name="q_label[<?= $qid ?>]" value="<?= e($qTx[$qid]['label'] ?? '') ?>"></td>
                    </tr>
                    <?php if (trim((string) ($q['help_text'] ?? '')) !== ''): ?>
                        <tr>
                            <td><?= e((string) $q['help_text']) ?><br><span class="muted"><?= t('nápověda') ?></span></td>
                            <td><input type="text" name="q_help[<?= $qid ?>]" value="<?= e($qTx[$qid]['help_text'] ?? '') ?>"></td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($options[$qid] ?? [] as $o): ?>
                        <?php $oid = (int) $o['id']; ?>
                        <tr>
                            <td style="padding-left:1.5rem">&bull; <?= e($o['label']) ?><br><span class="muted"><?= t('možnost') ?> <code><?= e($o['option_key']) ?></code></span></td>
                            <td><input type="text" name="o_label[<?= $oid ?>]" value="<?= e($oTx[$oid]['label'] ?? '') ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" class="btn btn--primary"><?= t('Uložit překlady ({lang})', ['lang' => e(I18n::name($lang))]) ?></button>
    </form>
</div>
