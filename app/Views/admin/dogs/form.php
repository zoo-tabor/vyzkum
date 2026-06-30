<?php
/** @var array<string, mixed>|null $dog */
/** @var array<int, array<string, mixed>> $breeds */
/** @var array<int, array<string, mixed>> $owners */
/** @var int|null $defaultBreedId */
/** @var string|null $error */

$isEdit = $dog !== null;
$action = $isEdit ? '/admin/dogs/' . (int) $dog['id'] : '/admin/dogs';
$v = static fn (string $key): string => e((string) ($dog[$key] ?? ''));
$breedSel = $isEdit ? (int) $dog['breed_id'] : (int) ($defaultBreedId ?? 0);
?>
<div class="page-head"><h1><?= $isEdit ? 'Upravit psa' : 'Nový pes' ?></h1></div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="<?= e($action) ?>">
        <?= \App\Core\Csrf::field() ?>

        <label for="breed_id">Plemeno *</label>
        <select id="breed_id" name="breed_id" required>
            <option value="">- vyberte -</option>
            <?php foreach ($breeds as $b): ?>
                <option value="<?= (int) $b['id'] ?>"<?= $breedSel === (int) $b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="name">Jméno psa *</label>
        <input type="text" id="name" name="name" value="<?= $v('name') ?>" required>

        <div class="form-row">
            <div><label for="kennel_name">Chovná stanice</label>
                <input type="text" id="kennel_name" name="kennel_name" value="<?= $v('kennel_name') ?>"></div>
            <div><label for="sex">Pohlaví</label>
                <select id="sex" name="sex">
                    <?php foreach (['unknown' => 'neznámé', 'male' => 'pes', 'female' => 'fena'] as $k => $lbl): ?>
                        <option value="<?= $k ?>"<?= ($dog['sex'] ?? 'unknown') === $k ? ' selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label for="birth_date">Datum narození</label>
                <input type="date" id="birth_date" name="birth_date" value="<?= $v('birth_date') ?>"></div>
        </div>

        <div class="form-row">
            <div><label for="chip_number">Číslo čipu</label>
                <input type="text" id="chip_number" name="chip_number" value="<?= $v('chip_number') ?>"></div>
            <div><label for="pedigree_number">Číslo průkazu</label>
                <input type="text" id="pedigree_number" name="pedigree_number" value="<?= $v('pedigree_number') ?>"></div>
            <div><label for="color">Barva</label>
                <input type="text" id="color" name="color" value="<?= $v('color') ?>"></div>
        </div>

        <label for="test_group">Testovací skupina</label>
        <input type="text" id="test_group" name="test_group" value="<?= $v('test_group') ?>">

        <?php if ($isEdit): ?>
            <div class="form-row">
                <div><label for="death_date">Datum úmrtí</label>
                    <input type="date" id="death_date" name="death_date" value="<?= $v('death_date') ?>"></div>
                <div><label for="death_cause">Příčina úmrtí</label>
                    <input type="text" id="death_cause" name="death_cause" value="<?= $v('death_cause') ?>"></div>
                <div></div>
            </div>
        <?php else: ?>
            <label for="owner_id">Majitel (nepovinné)</label>
            <select id="owner_id" name="owner_id">
                <option value="">- bez majitele -</option>
                <?php foreach ($owners as $o): ?>
                    <option value="<?= (int) $o['id'] ?>"><?= e($o['display_name']) ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <label for="health_summary">Zdravotní shrnutí</label>
        <textarea id="health_summary" name="health_summary" rows="3"><?= $v('health_summary') ?></textarea>

        <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Uložit změny' : 'Vytvořit psa' ?></button>
        <a class="btn" href="<?= $isEdit ? '/admin/dogs/' . (int) $dog['id'] : '/admin/dogs' ?>">Zrušit</a>
    </form>
</div>
