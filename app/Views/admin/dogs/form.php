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
?>
<div class="page-head"><h1><?= $isEdit ? t('Upravit psa') : t('Nový pes') ?></h1></div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="<?= e($action) ?>">
        <?= \App\Core\Csrf::field() ?>

        <label for="breed_id"><?= t('Plemeno') ?> *</label>
        <select id="breed_id" name="breed_id" required>
            <option value=""><?= t('- vyberte -') ?></option>
            <?php foreach ($breeds as $b): ?>
                <option value="<?= (int) $b['id'] ?>"<?= $breedSel === (int) $b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="name"><?= t('Jméno psa') ?> *</label>
        <input type="text" id="name" name="name" value="<?= $v('name') ?>" required>

        <div class="form-row">
            <div><label for="kennel_name"><?= t('Chovná stanice') ?></label>
                <input type="text" id="kennel_name" name="kennel_name" value="<?= $v('kennel_name') ?>"></div>
            <div><label for="sex"><?= t('Pohlaví') ?></label>
                <select id="sex" name="sex">
                    <?php foreach (['unknown' => tc('pohlaví', 'neznámé'), 'male' => tc('pohlaví', 'pes'), 'female' => tc('pohlaví', 'fena')] as $k => $lbl): ?>
                        <option value="<?= $k ?>"<?= ($dog['sex'] ?? 'unknown') === $k ? ' selected' : '' ?>><?= e($lbl) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label for="birth_date"><?= t('Datum narození') ?></label>
                <input type="date" id="birth_date" name="birth_date" value="<?= $v('birth_date') ?>"></div>
        </div>

        <div class="form-row">
            <div><label for="chip_number"><?= t('Číslo čipu') ?></label>
                <input type="text" id="chip_number" name="chip_number" value="<?= $v('chip_number') ?>"></div>
            <div><label for="pedigree_number"><?= t('Číslo průkazu') ?></label>
                <input type="text" id="pedigree_number" name="pedigree_number" value="<?= $v('pedigree_number') ?>"></div>
            <div><label for="country"><?= t('Země původu') ?></label>
                <select id="country" name="country">
                    <option value=""><?= t('- neuvedeno -') ?></option>
                    <?php foreach (Countries::all() as $code => $name): ?>
                        <option value="<?= e($code) ?>"<?= ($dog['country'] ?? '') === $code ? ' selected' : '' ?>><?= e($name) ?> (<?= e($code) ?>)</option>
                    <?php endforeach; ?>
                </select></div>
        </div>

        <div class="form-row">
            <div>
                <label for="color_select"><?= t('Barva') ?></label>
                <select id="color_select" name="color_select"></select>
                <input type="text" id="color_other" name="color_other" placeholder="<?= e(t('jiná barva')) ?>" style="display:none; margin-top:0.4rem">
            </div>
            <div><label for="test_group"><?= t('Testovací skupina') ?></label>
                <input type="text" id="test_group" name="test_group" value="<?= $v('test_group') ?>"></div>
            <div></div>
        </div>
        <p class="muted"><?= t('Datum izolace DNA a stav GWAS se nyní evidují u konkrétního vzorku (sekce Vzorky).') ?></p>

        <div class="form-row">
            <div><label for="castration_status"><?= t('Kastrace') ?></label>
                <?php
                $castrationOpts = ['' => t('- neuvedeno -'), 'intact' => t('nekastrovaný/á'), 'castrated' => t('kastrovaný/á')];
                $castCur = (string) ($dog['castration_status'] ?? '');
                if ($castCur !== '' && !isset($castrationOpts[$castCur])) {
                    $castrationOpts[$castCur] = $castCur; // zachovat pripadnou starsi volnou hodnotu
                }
                ?>
                <select id="castration_status" name="castration_status">
                    <?php foreach ($castrationOpts as $k => $lbl): ?>
                        <option value="<?= e($k) ?>"<?= $castCur === $k ? ' selected' : '' ?>><?= e($lbl) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div><label for="castration_date"><?= t('Datum kastrace') ?></label>
                <input type="date" id="castration_date" name="castration_date" value="<?= $v('castration_date') ?>"></div>
            <div></div>
        </div>

        <?php if ($isEdit): ?>
            <div class="form-row">
                <div><label for="death_date"><?= t('Datum úmrtí') ?></label>
                    <input type="date" id="death_date" name="death_date" value="<?= $v('death_date') ?>"></div>
                <div></div>
                <div></div>
            </div>

            <?php $causeTree = $causeTree ?? []; $causeId = (int) ($dog['death_cause_id'] ?? 0); ?>
            <div class="dog-cause">
                <label><?= t('Příčina úmrtí') ?></label>
                <?php if ($causeTree !== []): ?>
                    <div class="cause-picker" data-cause-picker data-selected="<?= $causeId ?>" data-placeholder="<?= e(t('– vyberte –')) ?>">
                        <div class="cause-levels"></div>
                        <input type="hidden" name="death_cause_id" value="<?= $causeId ?: '' ?>">
                        <div class="cause-note"<?= !empty($dog['death_cause_note']) ? '' : ' hidden' ?>>
                            <label for="death_cause_note"><?= t('Poznámka') ?></label>
                            <textarea id="death_cause_note" name="death_cause_note" rows="2"><?= e($dog['death_cause_note'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <?php if ($causeId === 0 && !empty($dog['death_cause'])): ?>
                        <p class="muted"><?= t('Aktuálně uvedeno (mimo číselník): {cause}', ['cause' => '<strong>' . e($dog['death_cause']) . '</strong>']) ?></p>
                    <?php endif; ?>
                    <p class="muted"><?= t('Vyplňte jen když je zadané datum úmrtí. Podbody se odkryjí po výběru nadřazené položky.') ?></p>
                <?php else: ?>
                    <input type="text" name="death_cause" value="<?= $v('death_cause') ?>" placeholder="<?= e(t('volný text')) ?>">
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="form-row">
                <div><label for="owner_id"><?= t('Majitel (nepovinné)') ?></label>
                    <select id="owner_id" name="owner_id">
                        <option value=""><?= t('- bez majitele -') ?></option>
                        <?php foreach ($owners as $o): ?>
                            <option value="<?= (int) $o['id'] ?>"><?= e($o['display_name']) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div></div>
                <div></div>
            </div>

            <fieldset>
                <legend><?= t('Vzorek (nepovinné)') ?></legend>
                <div class="form-row">
                    <div><label for="sample_id"><?= t('Číslo vzorku') ?></label>
                        <input type="text" id="sample_id" name="sample_id" placeholder="<?= e(t('např. CKCML1')) ?>"></div>
                    <div><label for="sample_received_at"><?= t('Datum přijetí vzorku') ?></label>
                        <input type="date" id="sample_received_at" name="sample_received_at"></div>
                    <div></div>
                </div>
                <p class="muted"><?= t('Vyplněné číslo vzorku se rovnou spáruje s tímto psem (napojení na genetiku).') ?></p>
            </fieldset>
        <?php endif; ?>

        <label for="health_summary"><?= t('Zdravotní shrnutí') ?></label>
        <textarea id="health_summary" name="health_summary" rows="3"><?= $v('health_summary') ?></textarea>

        <button type="submit" class="btn btn--primary"><?= $isEdit ? t('Uložit změny') : t('Vytvořit psa') ?></button>
        <a class="btn" href="<?= $isEdit ? '/admin/dogs/' . (int) $dog['id'] : '/admin/dogs' ?>"><?= t('Zrušit') ?></a>
    </form>
</div>

<?php if ($isEdit && ($causeTree ?? []) !== []): ?>
<script type="application/json" id="cause-tree"><?= json_encode($causeTree, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<script src="<?= e(asset('assets/cause-picker.js')) ?>"></script>
<?php endif; ?>
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
        var empty = document.createElement('option'); empty.value = ''; empty.textContent = <?= json_encode(t('- vyberte -'), JSON_UNESCAPED_UNICODE) ?>; colSel.appendChild(empty);
        list.forEach(function (name) {
            var o = document.createElement('option'); o.value = name; o.textContent = name; colSel.appendChild(o);
        });
        var other = document.createElement('option'); other.value = '__other__'; other.textContent = <?= json_encode(t('jiné...'), JSON_UNESCAPED_UNICODE) ?>; colSel.appendChild(other);

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
