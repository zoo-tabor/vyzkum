<?php
/** @var array<string, mixed> $dog */
/** @var array<string, mixed> $def */
/** @var array<int, array<string, mixed>> $questions */
/** @var array<int, array<int, array<string, mixed>>> $options */
/** @var array<int, array<string, mixed>> $diseaseTree */
/** @var string|null $error */
?>
<div class="page-head">
    <h1><?= e($def['name']) ?></h1>
    <p class="muted"><?= t('Pes:') ?> <?= e($dog['name']) ?> / <?= e(\App\Support\Breeds::translate($dog['breed_name'])) ?> &middot; <a href="/portal/dogs/<?= (int) $dog['id'] ?>"><?= t('zpět') ?></a></p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="/portal/dogs/<?= (int) $dog['id'] ?>/forms/<?= (int) $def['id'] ?>" enctype="multipart/form-data">
        <?= \App\Core\Csrf::field() ?>

        <?php foreach ($questions as $q): ?>
            <?php
            $qid = (int) $q['id'];
            $field = 'q_' . $qid;
            $type = (string) $q['type'];
            $req = ((int) $q['is_required']) === 1;
            $cfg = !empty($q['config_json']) ? (json_decode((string) $q['config_json'], true) ?: []) : [];
            $vi = $cfg['visible_if'] ?? null;
            $qOptions = $options[$qid] ?? [];
            ?>
            <div class="q-wrap" data-qkey="<?= e($q['question_key']) ?>" data-qtype="<?= e($type) ?>"
                 <?= $vi ? 'data-vq="' . e($vi['q']) . '" data-veq="' . e($vi['eq']) . '"' : '' ?>
                 style="margin-bottom:1rem">
                <label><strong><?= e($q['label']) ?></strong><?= $req ? ' *' : '' ?></label>
                <?php if (!empty($q['help_text'])): ?><div class="muted"><?= e($q['help_text']) ?></div><?php endif; ?>

                <?php if ($type === 'short_text'): ?>
                    <input type="text" name="<?= $field ?>" <?= $req ? 'required' : '' ?>>
                <?php elseif ($type === 'long_text'): ?>
                    <textarea name="<?= $field ?>" rows="3" <?= $req ? 'required' : '' ?>></textarea>
                <?php elseif ($type === 'number'): ?>
                    <input type="number" step="any" name="<?= $field ?>" <?= $req ? 'required' : '' ?> style="max-width:200px">
                <?php elseif ($type === 'date'): ?>
                    <input type="date" name="<?= $field ?>" <?= $req ? 'required' : '' ?> style="max-width:200px">
                <?php elseif ($type === 'file'): ?>
                    <input type="file" name="<?= $field ?>" accept=".pdf,.jpg,.jpeg,.png,.webp" <?= $req ? 'required' : '' ?>>
                <?php elseif ($type === 'yes_no'): ?>
                    <div><label><input type="radio" name="<?= $field ?>" value="yes"> <?= t('Ano') ?></label>
                        <label><input type="radio" name="<?= $field ?>" value="no"> <?= t('Ne') ?></label></div>
                <?php elseif ($type === 'single_choice'): ?>
                    <?php foreach ($qOptions as $o): ?>
                        <div><label><input type="radio" name="<?= $field ?>" value="<?= e($o['option_key']) ?>"> <?= e($o['label']) ?></label></div>
                    <?php endforeach; ?>
                <?php elseif ($type === 'multiple_choice'): ?>
                    <?php foreach ($qOptions as $o): ?>
                        <div><label><input type="checkbox" name="<?= $field ?>[]" value="<?= e($o['option_key']) ?>"> <?= e($o['label']) ?></label></div>
                    <?php endforeach; ?>
                <?php elseif ($type === 'disease_history'): ?>
                    <?php
                    $renderDisease = function (array $nodes, string $field) use (&$renderDisease): void { ?>
                        <ul class="dh-list">
                        <?php foreach ($nodes as $n): ?>
                            <?php if (empty($n['is_leaf'])): ?>
                                <li class="dh-cat">
                                    <label class="dh-cat-label"><input type="checkbox" class="dh-cat-toggle"> <strong><?= e($n['label']) ?></strong></label>
                                    <div class="dh-children" hidden><?php $renderDisease($n['children'], $field); ?></div>
                                </li>
                            <?php else: ?>
                                <li class="dh-leaf">
                                    <label><input type="checkbox" class="dh-leaf-check" name="<?= $field ?>_disease[]" value="<?= (int) $n['id'] ?>"> <?= e($n['label']) ?></label>
                                    <div class="dh-dates" hidden>
                                        <span class="dh-date"><?= t('od') ?> <input type="date" name="<?= $field ?>_from[<?= (int) $n['id'] ?>]"></span>
                                        <span class="dh-date"><?= t('do') ?> <input type="date" class="dh-to" name="<?= $field ?>_to[<?= (int) $n['id'] ?>]"></span>
                                        <label class="inline"><input type="checkbox" class="dh-ongoing" name="<?= $field ?>_ongoing[<?= (int) $n['id'] ?>]" value="1"> <?= t('stále probíhá') ?></label>
                                        <?php if (!empty($n['has_note'])): ?>
                                            <input type="text" class="dh-note" name="<?= $field ?>_note[<?= (int) $n['id'] ?>]" placeholder="<?= e(t('upřesnění (nepovinné)')) ?>">
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </ul>
                    <?php };
                    ?>
                    <div class="disease-history" data-dh>
                        <?php if (($diseaseTree ?? []) === []): ?>
                            <p class="muted"><?= t('Pro toto plemeno zatím není číselník nemocí.') ?></p>
                        <?php else: ?>
                            <p class="muted"><?= t('Zaškrtněte kategorii pro rozbalení nemocí, pak vyberte prodělané nemoci a zadejte období.') ?></p>
                            <?php $renderDisease($diseaseTree, $field); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <label for="note"><strong><?= t('Poznámka na závěr') ?></strong></label>
        <textarea id="note" name="note" rows="3" placeholder="<?= e(t('Cokoliv, co se do dotazníku nevejde...')) ?>"></textarea>

        <button type="submit" class="btn btn--primary"><?= t('Odeslat dotazník') ?></button>
    </form>
</div>

<style>
.disease-history .dh-list { list-style:none; margin:0; padding:0; }
.disease-history .dh-children { list-style:none; margin:.25rem 0 .5rem 1.5rem; padding:0; border-left:2px solid var(--line); padding-left:.75rem; }
.disease-history .dh-cat { margin:.3rem 0; }
.disease-history .dh-leaf { margin:.35rem 0; }
.disease-history .dh-dates { margin:.3rem 0 .6rem 1.75rem; display:flex; flex-wrap:wrap; gap:.75rem; align-items:center; }
.disease-history .dh-dates input[type=date] { max-width:180px; }
.disease-history .dh-note { max-width:280px; }
</style>
<script src="<?= e(asset('assets/disease-history.js')) ?>"></script>
<script>
(function () {
    function valueOf(qkey) {
        var w = document.querySelector('.q-wrap[data-qkey="' + qkey + '"]');
        if (!w) return null;
        var type = w.getAttribute('data-qtype');
        if (type === 'multiple_choice') {
            return Array.prototype.slice.call(w.querySelectorAll('input[type=checkbox]:checked')).map(function (c) { return c.value; });
        }
        if (type === 'single_choice' || type === 'yes_no') {
            var r = w.querySelector('input[type=radio]:checked');
            return r ? r.value : null;
        }
        var el = w.querySelector('input,select,textarea');
        return el ? el.value : null;
    }
    function recompute() {
        document.querySelectorAll('.q-wrap[data-vq]').forEach(function (w) {
            var val = valueOf(w.getAttribute('data-vq'));
            var eq = w.getAttribute('data-veq');
            var show = Array.isArray(val) ? val.indexOf(eq) !== -1 : String(val) === eq;
            w.style.display = show ? '' : 'none';
        });
    }
    document.addEventListener('change', recompute);
    recompute();
})();
</script>
