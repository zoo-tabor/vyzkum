<?php
/** @var array<string, mixed> $node */
/** @var string $breedName */
/** @var string|null $error */

$nid = (int) $node['id'];
$breedId = (int) $node['breed_id'];
?>
<div class="page-head">
    <h1><?= t('Upravit příčinu') ?></h1>
    <p><a href="/admin/death-causes?breed=<?= $breedId ?>">&larr; <?= t('Zpět na číselník') ?></a>
        <?php if ($breedName !== ''): ?><span class="muted">/ <?= e(\App\Support\Breeds::translate($breedName)) ?></span><?php endif; ?>
    </p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p class="muted"><?= t('Kód {code} je stabilní klíč – nemění se. Překlady názvu doplníte na obrazovce Překlady.', ['code' => '<code>' . e((string) $node['code']) . '</code>']) ?></p>

    <form method="post" action="/admin/death-causes/<?= $nid ?>">
        <?= \App\Core\Csrf::field() ?>

        <label for="label"><?= t('Název příčiny') ?> * <span class="muted">(<?= t('český zdroj') ?>)</span></label>
        <input type="text" id="label" name="label" required value="<?= e((string) $node['label']) ?>">

        <label class="inline" style="margin-top:.75rem"><input type="checkbox" name="has_note" value="1" <?= ((int) $node['has_note']) === 1 ? 'checked' : '' ?>> <?= t('Umožnit vlastní poznámku (např. „Jiné…“)') ?></label>

        <div style="margin-top:1rem">
            <button type="submit" class="btn btn--primary"><?= t('Uložit') ?></button>
            <a class="btn" href="/admin/death-causes?breed=<?= $breedId ?>"><?= t('Zrušit') ?></a>
        </div>
    </form>
</div>
