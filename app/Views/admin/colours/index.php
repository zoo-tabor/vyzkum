<?php
/** @var int|null $breedId */
/** @var array<int, array<string, mixed>> $breeds */
/** @var array<int, array<string, mixed>> $colours */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head"><h1>Barvy plemen</h1></div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Nová barva</h2>
    <form method="post" action="/admin/colours" class="form-row">
        <?= \App\Core\Csrf::field() ?>
        <div>
            <label for="breed_id">Plemeno</label>
            <select id="breed_id" name="breed_id" required>
                <option value="">- vyberte -</option>
                <?php foreach ($breeds as $b): ?>
                    <option value="<?= (int) $b['id'] ?>"<?= $breedId === (int) $b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="name">Barva (dle FCI)</label>
            <input type="text" id="name" name="name" placeholder="např. Black and Tan" required>
        </div>
        <div class="form-row__action"><button type="submit" class="btn btn--primary">Přidat</button></div>
    </form>
    <p class="muted">Barvy se pak nabízejí při zadávání psa daného plemene (plus volba „jiné").</p>
</div>

<div class="card">
    <h2>Barvy vybraného plemene</h2>
    <?php if ($breedId === null): ?>
        <p class="muted">Vyberte konkrétní plemeno v přepínači nahoře pro zobrazení jeho barev.</p>
    <?php elseif ($colours === []): ?>
        <p class="muted">Pro toto plemeno zatím nejsou žádné barvy.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Barva</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($colours as $c): ?>
                <tr>
                    <td><?= e($c['name']) ?></td>
                    <td>
                        <form method="post" action="/admin/colours/<?= (int) $c['id'] ?>/delete" class="inline" onsubmit="return confirm('Odebrat barvu?');">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" class="btn btn--ghost">Odebrat</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
