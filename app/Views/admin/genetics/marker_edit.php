<?php
/** @var array<string, mixed> $marker */
/** @var array<int, array<string, mixed>> $genes */
/** @var string|null $error */
?>
<div class="page-head">
    <h1><?= t('Upravit marker') ?> <code><?= e($marker['marker_code']) ?></code></h1>
    <p><a href="/admin/genetics/markers">&larr; <?= t('Zpět na geny a markery') ?></a></p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="/admin/genetics/markers/<?= (int) $marker['id'] ?>">
        <?= \App\Core\Csrf::field() ?>

        <div class="form-row">
            <div><label for="gene_id"><?= t('Gen') ?></label>
                <select id="gene_id" name="gene_id" required>
                    <?php foreach ($genes as $g): ?>
                        <option value="<?= (int) $g['id'] ?>"<?= (int) $marker['gene_id'] === (int) $g['id'] ? ' selected' : '' ?>><?= e($g['symbol']) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label for="marker_code"><?= t('Kód markeru') ?> *</label>
                <input type="text" id="marker_code" name="marker_code" value="<?= e($marker['marker_code']) ?>" required></div>
            <div><label for="locus"><?= t('Lokus') ?></label>
                <input type="text" id="locus" name="locus" value="<?= e($marker['locus'] ?? '') ?>"></div>
        </div>

        <div class="form-row">
            <div><label for="reference_allele"><?= t('Ref. alela') ?></label>
                <input type="text" id="reference_allele" name="reference_allele" value="<?= e($marker['reference_allele'] ?? '') ?>"></div>
            <div><label for="alternate_allele"><?= t('Alt. alela') ?></label>
                <input type="text" id="alternate_allele" name="alternate_allele" value="<?= e($marker['alternate_allele'] ?? '') ?>"></div>
            <div><label for="allowed_values"><?= t('Povolené hodnoty') ?></label>
                <input type="text" id="allowed_values" name="allowed_values" value="<?= e($marker['allowed_values'] ?? '') ?>" placeholder="GG,GC,CC"></div>
        </div>

        <button type="submit" class="btn btn--primary"><?= t('Uložit marker') ?></button>
        <a class="btn" href="/admin/genetics/markers"><?= t('Zrušit') ?></a>
    </form>
</div>
