<?php
/** @var array<string, mixed> $dog */
/** @var array<string, mixed> $def */
/** @var array<int, array<string, mixed>> $questions */
/** @var array<int, array<int, array<string, mixed>>> $options */
/** @var string|null $error */

$isDead = !empty($dog['death_date']);
?>
<div class="page-head">
    <h1><?= e($def['name']) ?></h1>
    <p class="muted">Pes: <?= e($dog['name']) ?> / <?= e($dog['breed_name']) ?> &middot; <a href="/portal/dogs/<?= (int) $dog['id'] ?>">zpět</a></p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="/portal/dogs/<?= (int) $dog['id'] ?>/forms/<?= (int) $def['id'] ?>" enctype="multipart/form-data">
        <?= \App\Core\Csrf::field() ?>

        <fieldset style="border:1px solid var(--line); border-radius:8px; padding:1rem; margin-bottom:1rem;">
            <legend><strong>Je pes naživu?</strong></legend>
            <label><input type="radio" name="builtin_alive" value="yes" <?= $isDead ? '' : 'checked' ?> onclick="document.getElementById('builtin-death').style.display='none'"> Ano, žije</label>
            <label><input type="radio" name="builtin_alive" value="no" <?= $isDead ? 'checked' : '' ?> onclick="document.getElementById('builtin-death').style.display='block'"> Ne, uhynul</label>
            <div id="builtin-death" style="display:<?= $isDead ? 'block' : 'none' ?>; margin-top:0.5rem">
                <label for="builtin_death_date">Datum úmrtí (DD.MM.RRRR)</label>
                <input type="text" id="builtin_death_date" name="builtin_death_date" placeholder="DD.MM.RRRR"
                       value="<?= e(\App\Support\Dates::toCz($dog['death_date'] ?? null)) ?>" style="max-width:200px">
            </div>
        </fieldset>

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
                    <div><label><input type="radio" name="<?= $field ?>" value="yes"> Ano</label>
                        <label><input type="radio" name="<?= $field ?>" value="no"> Ne</label></div>
                <?php elseif ($type === 'single_choice'): ?>
                    <?php foreach ($qOptions as $o): ?>
                        <div><label><input type="radio" name="<?= $field ?>" value="<?= e($o['option_key']) ?>"> <?= e($o['label']) ?></label></div>
                    <?php endforeach; ?>
                <?php elseif ($type === 'multiple_choice'): ?>
                    <?php foreach ($qOptions as $o): ?>
                        <div><label><input type="checkbox" name="<?= $field ?>[]" value="<?= e($o['option_key']) ?>"> <?= e($o['label']) ?></label></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <label for="note"><strong>Poznámka na závěr</strong></label>
        <textarea id="note" name="note" rows="3" placeholder="Cokoliv, co se do dotazníku nevejde..."></textarea>

        <button type="submit" class="btn btn--primary">Odeslat dotazník</button>
    </form>
</div>

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
