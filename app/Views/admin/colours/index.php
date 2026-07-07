<?php
/** @var int|null $breedId */
/** @var array<int, array<string, mixed>> $colours */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head"><h1><?= t('Barvy plemen') ?></h1></div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2><?= t('Nová barva') ?></h2>
    <?php if ($breedId === null): ?>
        <p class="muted"><?= t('Vyberte nejdříve plemeno v přepínači nahoře, poté můžete přidávat jeho barvy.') ?></p>
    <?php else: ?>
        <form method="post" action="/admin/colours" class="form-row">
            <?= \App\Core\Csrf::field() ?>
            <div>
                <label for="name"><?= t('Barva (dle FCI)') ?></label>
                <input type="text" id="name" name="name" placeholder="<?= e(t('např. Black and Tan')) ?>" required>
            </div>
            <div class="form-row__action"><button type="submit" class="btn btn--primary"><?= t('Přidat') ?></button></div>
        </form>
        <p class="muted"><?= t('Barva se přidá k plemeni vybranému v přepínači nahoře. Barvy se pak nabízejí při zadávání psa (plus volba „jiné").') ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <h2><?= t('Barvy vybraného plemene') ?></h2>
    <?php if ($breedId === null): ?>
        <p class="muted"><?= t('Vyberte konkrétní plemeno v přepínači nahoře pro zobrazení jeho barev.') ?></p>
    <?php elseif ($colours === []): ?>
        <p class="muted"><?= t('Pro toto plemeno zatím nejsou žádné barvy.') ?></p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th><?= t('Barva') ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($colours as $c): ?>
                <tr>
                    <td><?= e($c['name']) ?></td>
                    <td>
                        <form method="post" action="/admin/colours/<?= (int) $c['id'] ?>/delete" class="inline" onsubmit="return confirm(<?= e(json_encode(t('Odebrat barvu?'), JSON_UNESCAPED_UNICODE)) ?>);">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" class="btn btn--ghost"><?= t('Odebrat') ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
