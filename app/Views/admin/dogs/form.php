<?php
/** @var array<string, mixed>|null $dog */
/** @var array<int, array<string, mixed>> $breeds */
/** @var array<int, array<string, mixed>> $owners */
/** @var array<int, array<int, string>> $coloursByBreed */
/** @var int|null $defaultBreedId */
/** @var string|null $error */

use App\Support\Countries;

$isEdit = $dog !== null;
$action = $isEdit ? '/admin/dogs/' . (int) $dog['id'] : '/admin/dogs';
$v = static fn (string $key): string => e((string) ($dog[$key] ?? ''));
$breedSel = $isEdit ? (int) $dog['breed_id'] : (int) ($defaultBreedId ?? 0);
$gwas = (string) ($dog['gwas_status'] ?? '');
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
            <div><label for="country">Země původu</label>
                <select id="country" name="country">
                    <option value="">- neuvedeno -</option>
                    <?php foreach (Countries::all() as $code => $name): ?>
                        <option value="<?= e($code) ?>"<?= ($dog['country'] ?? '') === $code ? ' selected' : '' ?>><?= e($name) ?> (<?= e($code) ?>)</option>
                    <?php endforeach; ?>
                </select></div>
        </div>

        <div class="form-row">
            <div>
                <label for="color_select">Barva</label>
                <select id="color_select" name="color_select"></select>
                <input type="text" id="color_other" name="color_other" placeholder="jiná barva" style="display:none; margin-top:0.4rem">
            </div>
            <div><label for="test_group">Testovací skupina</label>
                <input type="text" id="test_group" name="test_group" value="<?= $v('test_group') ?>"></div>
            <div><label for="gwas_status">GWAS</label>
                <select id="gwas_status" name="gwas_status">
                    <?php foreach (['' => '- neuvedeno -', 'GWAS_sent' => 'GWAS_sent', 'GWAS_ok' => 'GWAS_ok', 'GWAS_failed' => 'GWAS_failed'] as $k => $lbl): ?>
                        <option value="<?= e($k) ?>"<?= $gwas === $k ? ' selected' : '' ?>><?= e($lbl) ?></option>
                    <?php endforeach; ?>
                </select></div>
        </div>

        <?php if ($isEdit): ?>
            <div class="form-row">
                <div><label for="death_date">Datum úmrtí</label>
                    <input type="date" id="death_date" name="death_date" value="<?= $v('death_date') ?>"></div>
                <div><label for="death_cause">Příčina úmrtí</label>
                    <input type="text" id="death_cause" name="death_cause" value="<?= $v('death_cause') ?>"></div>
                <div><label for="dna_isolated_at">Datum izolace DNA</label>
                    <input type="date" id="dna_isolated_at" name="dna_isolated_at" value="<?= $v('dna_isolated_at') ?>"></div>
            </div>
        <?php else: ?>
            <div class="form-row">
                <div><label for="owner_id">Majitel (nepovinné)</label>
                    <select id="owner_id" name="owner_id">
                        <option value="">- bez majitele -</option>
                        <?php foreach ($owners as $o): ?>
                            <option value="<?= (int) $o['id'] ?>"><?= e($o['display_name']) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div><label for="dna_isolated_at">Datum izolace DNA</label>
                    <input type="date" id="dna_isolated_at" name="dna_isolated_at" value=""></div>
                <div></div>
            </div>

            <fieldset>
                <legend>Vzorek (nepovinné)</legend>
                <div class="form-row">
                    <div><label for="sample_id">Číslo vzorku</label>
                        <input type="text" id="sample_id" name="sample_id" placeholder="např. CKCML1"></div>
                    <div><label for="sample_received_at">Datum přijetí vzorku</label>
                        <input type="date" id="sample_received_at" name="sample_received_at"></div>
                    <div></div>
                </div>
                <p class="muted">Vyplněné číslo vzorku se rovnou spáruje s tímto psem (napojení na genetiku).</p>
            </fieldset>
        <?php endif; ?>

        <label for="health_summary">Zdravotní shrnutí</label>
        <textarea id="health_summary" name="health_summary" rows="3"><?= $v('health_summary') ?></textarea>

        <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Uložit změny' : 'Vytvořit psa' ?></button>
        <a class="btn" href="<?= $isEdit ? '/admin/dogs/' . (int) $dog['id'] : '/admin/dogs' ?>">Zrušit</a>
    </form>
</div>

<script>
(function () {
    var COLOURS = <?= json_encode($coloursByBreed, JSON_UNESCAPED_UNICODE) ?>;
    var CURRENT = <?= json_encode($dog['color'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
    var breedSel = document.getElementById('breed_id');
    var colSel = document.getElementById('color_select');
    var colOther = document.getElementById('color_other');

    function rebuild(keepValue) {
        var breedId = breedSel.value;
        var list = (COLOURS[breedId] || []);
        var want = keepValue !== undefined ? keepValue : CURRENT;
        colSel.innerHTML = '';
        var empty = document.createElement('option'); empty.value = ''; empty.textContent = '- vyberte -'; colSel.appendChild(empty);
        list.forEach(function (name) {
            var o = document.createElement('option'); o.value = name; o.textContent = name; colSel.appendChild(o);
        });
        var other = document.createElement('option'); other.value = '__other__'; other.textContent = 'jiné...'; colSel.appendChild(other);

        if (want && list.indexOf(want) === -1) {
            colSel.value = '__other__'; colOther.style.display = ''; colOther.value = want;
        } else {
            colSel.value = want || ''; colOther.style.display = 'none'; colOther.value = '';
        }
    }
    colSel.addEventListener('change', function () {
        if (colSel.value === '__other__') { colOther.style.display = ''; }
        else { colOther.style.display = 'none'; colOther.value = ''; }
    });
    breedSel.addEventListener('change', function () { rebuild(''); });
    rebuild();
})();
</script>
