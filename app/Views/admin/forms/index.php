<?php
/** @var array<int, array<string, mixed>> $forms */
/** @var array<int, array<string, mixed>> $breeds */
/** @var int|null $currentBreedId */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head"><h1>Formulare (dotazniky)</h1></div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Novy dotaznik</h2>
    <form method="post" action="/admin/forms" class="form-row">
        <?= \App\Core\Csrf::field() ?>
        <div>
            <label for="breed_id">Plemeno</label>
            <select id="breed_id" name="breed_id" required>
                <option value="">- vyberte -</option>
                <?php foreach ($breeds as $b): ?>
                    <option value="<?= (int) $b['id'] ?>"<?= $currentBreedId === (int) $b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="name">Nazev</label>
            <input type="text" id="name" name="name" placeholder="napr. Uvodni dotaznik" required>
        </div>
        <div class="form-row__action"><button type="submit" class="btn btn--primary">Vytvorit</button></div>
    </form>
</div>

<div class="card">
    <h2>Seznam (<?= count($forms) ?>)</h2>
    <?php if ($forms === []): ?>
        <p class="muted">Zatim zadne dotazniky<?= $currentBreedId !== null ? ' pro vybrane plemeno' : '' ?>.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Nazev</th><th>Plemeno</th><th>Stav</th><th>Verze</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($forms as $f): ?>
                <tr>
                    <td><a href="/admin/forms/<?= (int) $f['id'] ?>"><?= e($f['name']) ?></a></td>
                    <td><?= e($f['breed_name']) ?></td>
                    <td><?= e($f['status']) ?><?= (int) $f['draft_count'] > 0 ? ' (+draft)' : '' ?></td>
                    <td><?= (int) $f['latest_version'] ?></td>
                    <td><a href="/admin/forms/<?= (int) $f['id'] ?>">Otevrit &rarr;</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
