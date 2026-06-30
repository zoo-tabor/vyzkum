<?php
/** @var array<int, array<string, mixed>> $breeds */
/** @var string|null $error */
/** @var string|null $notice */
?>
<div class="page-head">
    <h1>Plemena</h1>
</div>

<?php if (!empty($notice)): ?>
    <div class="alert alert--ok"><?= e($notice) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert--error"><?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <h2>Nové plemeno</h2>
    <form method="post" action="/admin/breeds" class="form-row">
        <?= \App\Core\Csrf::field() ?>
        <div>
            <label for="name">Název</label>
            <input type="text" id="name" name="name" value="<?= old('name') ?>" required>
        </div>
        <div>
            <label for="slug">Slug (nepovinné)</label>
            <input type="text" id="slug" name="slug" value="<?= old('slug') ?>" placeholder="napr. border-collie">
        </div>
        <div class="form-row__action">
            <button type="submit" class="btn btn--primary">Přidat</button>
        </div>
    </form>
    <p class="muted">Slug je po vytvoření neměnný a používá se interně (URL, exporty). Z uživatelského vstupu se nikdy neskládají názvy DB objektů.</p>
</div>

<div class="card">
    <h2>Seznam plemen (<?= count($breeds) ?>)</h2>
    <?php if ($breeds === []): ?>
        <p class="muted">Zatím nejsou žádná plemena.</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr><th>ID</th><th>Název</th><th>Slug</th><th>Stav</th></tr>
            </thead>
            <tbody>
            <?php foreach ($breeds as $breed): ?>
                <tr>
                    <td><?= (int) $breed['id'] ?></td>
                    <td><?= e($breed['name']) ?></td>
                    <td><code><?= e($breed['slug']) ?></code></td>
                    <td><?= ((int) $breed['is_active']) === 1 ? 'aktivní' : 'neaktivní' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
