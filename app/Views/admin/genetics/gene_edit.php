<?php
/** @var array<string, mixed> $gene */
/** @var string|null $error */
?>
<div class="page-head">
    <h1><?= t('Upravit gen') ?> <code><?= e($gene['symbol']) ?></code></h1>
    <p><a href="/admin/genetics/markers">&larr; <?= t('Zpět na geny a markery') ?></a></p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="/admin/genetics/genes/<?= (int) $gene['id'] ?>">
        <?= \App\Core\Csrf::field() ?>

        <label for="symbol"><?= t('Symbol') ?> *</label>
        <input type="text" id="symbol" name="symbol" value="<?= e($gene['symbol']) ?>" required>

        <div class="form-row">
            <div><label for="name"><?= t('Název') ?></label>
                <input type="text" id="name" name="name" value="<?= e($gene['name'] ?? '') ?>"></div>
            <div><label for="note"><?= t('Poznámka') ?></label>
                <input type="text" id="note" name="note" value="<?= e($gene['note'] ?? '') ?>"></div>
        </div>

        <label for="description"><?= t('Popis') ?></label>
        <textarea id="description" name="description" rows="2"><?= e($gene['description'] ?? '') ?></textarea>

        <button type="submit" class="btn btn--primary"><?= t('Uložit gen') ?></button>
        <a class="btn" href="/admin/genetics/markers"><?= t('Zrušit') ?></a>
    </form>
</div>
