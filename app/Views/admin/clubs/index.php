<?php
/** @var array<int, array<string, mixed>> $clubs */
/** @var array<int, array<string, mixed>> $breeds */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head"><h1>Klubove ucty</h1></div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Novy klubovy ucet</h2>
    <form method="post" action="/admin/clubs">
        <?= \App\Core\Csrf::field() ?>
        <label for="email">E-mail klubu</label>
        <input type="email" id="email" name="email" required style="max-width:360px">

        <label>Pristup k plemenum</label>
        <div style="columns:2; max-width:520px;">
            <?php foreach ($breeds as $b): ?>
                <label class="inline" style="display:block"><input type="checkbox" name="breeds[]" value="<?= (int) $b['id'] ?>"> <?= e($b['name']) ?></label>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn--primary">Vytvorit a poslat pozvanku</button>
    </form>
    <p class="muted">Vytvori ucet s roli klub (read-only) a posle odkaz pro nastaveni hesla.</p>
</div>

<div class="card">
    <h2>Existujici kluby (<?= count($clubs) ?>)</h2>
    <?php if ($clubs === []): ?>
        <p class="muted">Zatim zadne klubove ucty.</p>
    <?php else: ?>
        <?php foreach ($clubs as $c): ?>
            <div style="border-top:1px solid var(--line); padding-top:0.75rem; margin-top:0.75rem;">
                <strong><?= e($c['email']) ?></strong>
                <?= empty($c['password_hash']) ? '<span class="muted">(heslo zatim nenastaveno)</span>' : '' ?>
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
                    <button type="submit" class="btn">Ulozit pristup</button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
