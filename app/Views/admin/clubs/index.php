<?php
/** @var array<int, array<string, mixed>> $clubs */
/** @var array<int, array<string, mixed>> $breeds */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head"><h1><?= t('Klubové účty') ?></h1></div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2><?= t('Nový klubový účet') ?></h2>
    <form method="post" action="/admin/clubs">
        <?= \App\Core\Csrf::field() ?>
        <label for="email"><?= t('E-mail klubu') ?></label>
        <input type="email" id="email" name="email" required style="max-width:360px">

        <label><?= t('Přístup k plemenům') ?></label>
        <div style="columns:2; max-width:520px;">
            <?php foreach ($breeds as $b): ?>
                <label class="inline" style="display:block"><input type="checkbox" name="breeds[]" value="<?= (int) $b['id'] ?>"> <?= e($b['name']) ?></label>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn--primary"><?= t('Vytvořit a poslat pozvánku') ?></button>
    </form>
    <p class="muted"><?= t('Vytvoří účet s rolí klub (read-only) a pošle odkaz pro nastavení hesla.') ?></p>
</div>

<div class="card">
    <h2><?= t('Existující kluby') ?> (<?= count($clubs) ?>)</h2>
    <?php if ($clubs === []): ?>
        <p class="muted"><?= t('Zatím žádné klubové účty.') ?></p>
    <?php else: ?>
        <?php foreach ($clubs as $c): ?>
            <div style="border-top:1px solid var(--line); padding-top:0.75rem; margin-top:0.75rem;">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem;">
                    <span>
                        <strong><?= e($c['email']) ?></strong>
                        <?= empty($c['password_hash']) ? '<span class="muted">(' . t('heslo zatím nenastaveno') . ')</span>' : '' ?>
                    </span>
                    <form method="post" action="/admin/clubs/<?= (int) $c['id'] ?>/delete" style="margin:0;"
                          onsubmit="return confirm(<?= e(json_encode(t('Opravdu smazat klubový účet {email}?', ['email' => $c['email']]), JSON_UNESCAPED_UNICODE)) ?>);">
                        <?= \App\Core\Csrf::field() ?>
                        <button type="submit" class="btn btn--danger"><?= t('Smazat') ?></button>
                    </form>
                </div>
                <form method="post" action="/admin/clubs/<?= (int) $c['id'] ?>/breeds">
                    <?= \App\Core\Csrf::field() ?>
                    <div style="columns:2; max-width:520px; margin:0.5rem 0;">
                        <?php foreach ($breeds as $b): ?>
                            <label class="inline" style="display:block">
                                <input type="checkbox" name="breeds[]" value="<?= (int) $b['id'] ?>"<?= in_array((int) $b['id'], $c['breed_ids'], true) ? ' checked' : '' ?>>
                                <?= e($b['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn"><?= t('Uložit přístup') ?></button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
