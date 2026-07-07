<?php
/** @var array<string, mixed> $sample */
/** @var string|null $error */

use App\Support\Gwas;

$sid = (string) $sample['sample_id'];
$gwasCur = (string) ($sample['gwas_status'] ?? '');
?>
<div class="page-head">
    <h1><?= t('Upravit vzorek') ?> <code><?= e($sid) ?></code></h1>
    <p><a href="/admin/samples/<?= e(rawurlencode($sid)) ?>">&larr; <?= t('Zpět na vzorek') ?></a></p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="/admin/samples/<?= e(rawurlencode($sid)) ?>/edit">
        <?= \App\Core\Csrf::field() ?>
        <div class="form-row">
            <div><label for="dna_isolated_at"><?= t('Datum izolace DNA') ?></label>
                <input type="date" id="dna_isolated_at" name="dna_isolated_at"
                       value="<?= e(substr((string) ($sample['dna_isolated_at'] ?? ''), 0, 10)) ?>"></div>
            <div><label for="gwas_status">GWAS</label>
                <select id="gwas_status" name="gwas_status">
                    <?php foreach (Gwas::options() as $k => $lbl): ?>
                        <option value="<?= e($k) ?>"<?= $gwasCur === $k ? ' selected' : '' ?>><?= e($lbl) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div></div>
        </div>

        <label for="note"><?= t('Poznámka') ?></label>
        <textarea id="note" name="note" rows="3"><?= e($sample['note'] ?? '') ?></textarea>

        <button type="submit" class="btn btn--primary"><?= t('Uložit') ?></button>
        <a class="btn" href="/admin/samples/<?= e(rawurlencode($sid)) ?>"><?= t('Zrušit') ?></a>
    </form>
</div>
