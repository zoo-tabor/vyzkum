<?php
/** @var array<string, mixed> $def */
/** @var array<string, mixed>|null $draft */
/** @var array<string, mixed>|null $published */
/** @var array<string, mixed>|null $editing */
/** @var bool $canEdit */
/** @var array<int, array<string, mixed>> $questions */
/** @var array<int, array<int, array<string, mixed>>> $options */
/** @var string|null $notice */
/** @var string|null $error */

use App\Support\FormSchema;

$defId = (int) $def['id'];
?>
<div class="page-head">
    <h1><?= e($def['name']) ?> <span class="muted">/ <?= e($def['breed_name']) ?></span></h1>
    <p><a href="/admin/forms">&larr; Zpet na seznam</a></p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p>
        <?php if ($editing !== null): ?>
            Upravovana verze: <strong>v<?= (int) $editing['version'] ?> (<?= e($editing['status']) ?>)</strong>.
        <?php endif; ?>
        <?php if ($published !== null): ?>
            Publikovana verze: <strong>v<?= (int) $published['version'] ?></strong>.
        <?php else: ?>
            <span class="muted">Zatim nepublikovano.</span>
        <?php endif; ?>
    </p>
    <?php if ($canEdit): ?>
        <form method="post" action="/admin/forms/<?= $defId ?>/publish" class="inline"
              onsubmit="return confirm('Publikovat tuto verzi? Po publikaci uz nepujde menit (vznikne nova verze az pri dalsi uprave).');">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn--primary">Publikovat verzi</button>
        </form>
    <?php else: ?>
        <form method="post" action="/admin/forms/<?= $defId ?>/new-version" class="inline">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn">Vytvorit novou verzi (pro upravy)</button>
        </form>
        <span class="muted">Publikovana verze je zamcena.</span>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Otazky (<?= count($questions) ?>)</h2>
    <?php if ($questions === []): ?>
        <p class="muted">Zatim zadne otazky.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>#</th><th>Otazka</th><th>Typ</th><th>Povinna</th><th>Detail</th><?php if ($canEdit): ?><th>Akce</th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($questions as $i => $q): ?>
                <?php
                $cfg = !empty($q['config_json']) ? (json_decode((string) $q['config_json'], true) ?: []) : [];
                $visibleIf = $cfg['visible_if'] ?? null;
                $qOptions = $options[(int) $q['id']] ?? [];
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($q['label']) ?><br><span class="muted"><code><?= e($q['question_key']) ?></code></span></td>
                    <td><?= e(FormSchema::TYPES[$q['type']] ?? $q['type']) ?></td>
                    <td><?= ((int) $q['is_required']) === 1 ? 'ano' : 'ne' ?></td>
                    <td>
                        <?php if ($qOptions !== []): ?>
                            <span class="muted">moznosti: <?= e(implode(', ', array_map(static fn ($o) => $o['label'], $qOptions))) ?></span><br>
                        <?php endif; ?>
                        <?php if ($visibleIf !== null): ?>
                            <span class="muted">zobrazit kdyz <code><?= e($visibleIf['q']) ?></code> = <code><?= e($visibleIf['eq']) ?></code></span>
                        <?php endif; ?>
                    </td>
                    <?php if ($canEdit): ?>
                        <td style="white-space:nowrap">
                            <form method="post" action="/admin/forms/<?= $defId ?>/questions/<?= (int) $q['id'] ?>/move" class="inline">
                                <?= \App\Core\Csrf::field() ?><input type="hidden" name="dir" value="up"><button class="btn btn--ghost" type="submit">&uarr;</button>
                            </form>
                            <form method="post" action="/admin/forms/<?= $defId ?>/questions/<?= (int) $q['id'] ?>/move" class="inline">
                                <?= \App\Core\Csrf::field() ?><input type="hidden" name="dir" value="down"><button class="btn btn--ghost" type="submit">&darr;</button>
                            </form>
                            <a class="btn btn--ghost" href="/admin/forms/<?= $defId ?>/questions/<?= (int) $q['id'] ?>/edit">Upravit</a>
                            <form method="post" action="/admin/forms/<?= $defId ?>/questions/<?= (int) $q['id'] ?>/delete" class="inline"
                                  onsubmit="return confirm('Smazat otazku?');">
                                <?= \App\Core\Csrf::field() ?><button class="btn btn--ghost" type="submit">Smazat</button>
                            </form>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if ($canEdit): ?>
    <div class="card">
        <h2>Pridat otazku</h2>
        <form method="post" action="/admin/forms/<?= $defId ?>/questions">
            <?= \App\Core\Csrf::field() ?>

            <label for="label">Text otazky *</label>
            <input type="text" id="label" name="label" required>

            <div class="form-row">
                <div>
                    <label for="type">Typ *</label>
                    <select id="type" name="type" required>
                        <?php foreach (FormSchema::TYPES as $k => $lbl): ?>
                            <option value="<?= $k ?>"><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="help_text">Napoveda (nepovinne)</label>
                    <input type="text" id="help_text" name="help_text">
                </div>
                <div>
                    <label>&nbsp;</label>
                    <label class="inline"><input type="checkbox" name="is_required" value="1"> Povinna</label>
                </div>
            </div>

            <label for="options">Moznosti (jen pro "volby") - jedna na radek, volitelne <code>klic|popisek</code></label>
            <textarea id="options" name="options" rows="3" placeholder="Ano&#10;Ne"></textarea>

            <div class="form-row">
                <div>
                    <label for="visible_if_question">Zobrazit jen kdyz otazka (klic)</label>
                    <select id="visible_if_question" name="visible_if_question">
                        <option value="">- vzdy zobrazit -</option>
                        <?php foreach ($questions as $q): ?>
                            <option value="<?= e($q['question_key']) ?>"><?= e($q['question_key']) ?> (<?= e($q['label']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="visible_if_value">ma hodnotu (klic moznosti / "yes" / "no")</label>
                    <input type="text" id="visible_if_value" name="visible_if_value">
                </div>
                <div>
                    <label for="health_event_type">Zaznamenat jako zdravotni udalost</label>
                    <select id="health_event_type" name="health_event_type">
                        <option value="">- ne -</option>
                        <?php foreach (\App\Repositories\HealthEventRepository::TYPES as $t): ?>
                            <option value="<?= e($t) ?>"><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn--primary">Pridat otazku</button>
        </form>
        <p class="muted">Priklad podminene otazky: "datum umrti" se zobrazi jen kdyz otazka <code>je_pes_nazivu</code> = <code>no</code>.</p>
    </div>
<?php endif; ?>
