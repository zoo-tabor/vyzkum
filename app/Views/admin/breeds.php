<?php
/** @var array<int, array<string, mixed>> $breeds */
/** @var string|null $error */
/** @var string|null $notice */
?>
<div class="page-head">
    <h1><?= t('Plemena') ?></h1>
</div>

<?php if (!empty($notice)): ?>
    <div class="alert alert--ok"><?= e($notice) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2><?= t('Nové plemeno') ?></h2>
    <form method="post" action="/admin/breeds" class="form-row">
        <?= \App\Core\Csrf::field() ?>
        <div>
            <label for="name"><?= t('Název') ?></label>
            <input type="text" id="name" name="name" value="<?= old('name') ?>" required>
        </div>
        <div>
            <label for="slug"><?= t('Slug (nepovinné)') ?></label>
            <input type="text" id="slug" name="slug" value="<?= old('slug') ?>" placeholder="napr. border-collie">
        </div>
        <div class="form-row__action">
            <button type="submit" class="btn btn--primary"><?= t('Přidat') ?></button>
        </div>
    </form>
    <p class="muted"><?= t('Slug je po vytvoření neměnný a používá se interně (URL, exporty). Z uživatelského vstupu se nikdy neskládají názvy DB objektů.') ?></p>
</div>

<div class="card">
    <h2><?= t('Seznam plemen') ?> (<?= count($breeds) ?>)</h2>
    <?php if ($breeds === []): ?>
        <p class="muted"><?= t('Zatím nejsou žádná plemena.') ?></p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr><th>ID</th><th><?= t('Název') ?></th><th><?= t('Slug') ?></th><th><?= t('Stav') ?></th><th><?= t('Překlady') ?></th></tr>
            </thead>
            <tbody>
            <?php foreach ($breeds as $breed): ?>
                <tr>
                    <td><?= (int) $breed['id'] ?></td>
                    <td><?= e($breed['name']) ?></td>
                    <td><code><?= e($breed['slug']) ?></code></td>
                    <td><?= ((int) $breed['is_active']) === 1 ? t('aktivní') : t('neaktivní') ?></td>
                    <td><a class="btn btn--ghost" href="/admin/breeds/<?= (int) $breed['id'] ?>/translations"><?= t('Překlady') ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
