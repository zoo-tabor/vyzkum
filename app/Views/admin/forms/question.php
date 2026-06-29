<?php
/** @var int $defId */
/** @var array<string, mixed> $question */
/** @var array<int, array<string, mixed>> $options */
/** @var array<int, array<string, mixed>> $otherQuestions */
/** @var string|null $error */

use App\Support\FormSchema;

$cfg = !empty($question['config_json']) ? (json_decode((string) $question['config_json'], true) ?: []) : [];
$visibleIf = $cfg['visible_if'] ?? null;
$healthEvent = $cfg['health_event']['type'] ?? '';
$optionsText = implode("\n", array_map(static fn ($o) => $o['option_key'] . '|' . $o['label'], $options));
?>
<div class="page-head">
    <h1>Upravit otazku</h1>
    <p><a href="/admin/forms/<?= $defId ?>">&larr; Zpet na dotaznik</a></p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="/admin/forms/<?= $defId ?>/questions/<?= (int) $question['id'] ?>">
        <?= \App\Core\Csrf::field() ?>

        <p class="muted">Klic otazky: <code><?= e($question['question_key']) ?></code> (nemenny)</p>

        <label for="label">Text otazky *</label>
        <input type="text" id="label" name="label" value="<?= e($question['label']) ?>" required>

        <div class="form-row">
            <div>
                <label for="type">Typ *</label>
                <select id="type" name="type" required>
                    <?php foreach (FormSchema::TYPES as $k => $lbl): ?>
                        <option value="<?= $k ?>"<?= $question['type'] === $k ? ' selected' : '' ?>><?= e($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="help_text">Napoveda</label>
                <input type="text" id="help_text" name="help_text" value="<?= e($question['help_text'] ?? '') ?>">
            </div>
            <div>
                <label>&nbsp;</label>
                <label class="inline"><input type="checkbox" name="is_required" value="1"<?= ((int) $question['is_required']) === 1 ? ' checked' : '' ?>> Povinna</label>
            </div>
        </div>

        <label for="options">Moznosti (jen pro "volby") - jedna na radek, <code>klic|popisek</code></label>
        <textarea id="options" name="options" rows="3"><?= e($optionsText) ?></textarea>

        <div class="form-row">
            <div>
                <label for="visible_if_question">Zobrazit jen kdyz otazka (klic)</label>
                <select id="visible_if_question" name="visible_if_question">
                    <option value="">- vzdy zobrazit -</option>
                    <?php foreach ($otherQuestions as $q): ?>
                        <option value="<?= e($q['question_key']) ?>"<?= ($visibleIf['q'] ?? '') === $q['question_key'] ? ' selected' : '' ?>>
                            <?= e($q['question_key']) ?> (<?= e($q['label']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="visible_if_value">ma hodnotu</label>
                <input type="text" id="visible_if_value" name="visible_if_value" value="<?= e($visibleIf['eq'] ?? '') ?>">
            </div>
            <div>
                <label for="health_event_type">Zaznamenat jako zdravotni udalost</label>
                <select id="health_event_type" name="health_event_type">
                    <option value="">- ne -</option>
                    <?php foreach (\App\Repositories\HealthEventRepository::TYPES as $t): ?>
                        <option value="<?= e($t) ?>"<?= $healthEvent === $t ? ' selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn--primary">Ulozit otazku</button>
        <a class="btn" href="/admin/forms/<?= $defId ?>">Zrusit</a>
    </form>
</div>
