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
    <h1><?= t('Upravit otázku') ?></h1>
    <p><a href="/admin/forms/<?= $defId ?>">&larr; <?= t('Zpět na dotazník') ?></a></p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <form method="post" action="/admin/forms/<?= $defId ?>/questions/<?= (int) $question['id'] ?>">
        <?= \App\Core\Csrf::field() ?>

        <p class="muted"><?= t('Klíč otázky: {key} (neměnný)', ['key' => '<code>' . e($question['question_key']) . '</code>']) ?></p>

        <label for="label"><?= t('Text otázky') ?> *</label>
        <input type="text" id="label" name="label" value="<?= e($question['label']) ?>" required>

        <div class="form-row">
            <div>
                <label for="type"><?= t('Typ') ?> *</label>
                <select id="type" name="type" required>
                    <?php foreach (FormSchema::TYPES as $k => $lbl): ?>
                        <option value="<?= $k ?>"<?= $question['type'] === $k ? ' selected' : '' ?>><?= e(FormSchema::typeLabel($k)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="help_text"><?= t('Nápověda') ?></label>
                <input type="text" id="help_text" name="help_text" value="<?= e($question['help_text'] ?? '') ?>">
            </div>
            <div>
                <label>&nbsp;</label>
                <label class="inline"><input type="checkbox" name="is_required" value="1"<?= ((int) $question['is_required']) === 1 ? ' checked' : '' ?>> <?= t('Povinná') ?></label>
            </div>
        </div>

        <div data-qtypes="single_choice,multiple_choice">
            <label for="options"><?= t('Možnosti (jedna na řádek, volitelně {format})', ['format' => '<code>klíč|popisek</code>']) ?></label>
            <textarea id="options" name="options" rows="3"><?= e($optionsText) ?></textarea>
        </div>
        <div data-qtypes="disease_history">
            <p class="muted"><?= t('Použije se číselník nemocí plemene (větev nemocí z příčin úmrtí). Možnosti se nevyplňují - majitel vybere prodělané nemoci a jejich období.') ?></p>
        </div>
        <div data-qtypes="death_cause">
            <p class="muted"><?= t('Majitel zadá datum úmrtí a příčinu z číselníku plemene. Uloží se jako úmrtí psa (report + zdravotní událost), stejně jako hlášení z karty psa. Možnosti se nevyplňují.') ?></p>
        </div>

        <div class="form-row">
            <div>
                <label for="visible_if_question"><?= t('Zobrazit jen když otázka (klíč)') ?></label>
                <select id="visible_if_question" name="visible_if_question">
                    <option value=""><?= t('- vždy zobrazit -') ?></option>
                    <?php foreach ($otherQuestions as $q): ?>
                        <option value="<?= e($q['question_key']) ?>"<?= ($visibleIf['q'] ?? '') === $q['question_key'] ? ' selected' : '' ?>>
                            <?= e($q['question_key']) ?> (<?= e($q['label']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div data-cond-map="<?= e(json_encode($conditionValues ?? [], JSON_UNESCAPED_UNICODE)) ?>">
                <label for="visible_if_value"><?= t('má hodnotu') ?></label>
                <select id="visible_if_value" name="visible_if_value" data-cond-value data-cond-current="<?= e($visibleIf['eq'] ?? '') ?>" hidden disabled></select>
                <input type="text" name="visible_if_value" data-cond-text value="<?= e($visibleIf['eq'] ?? '') ?>" placeholder="<?= e(t('klíč možnosti / yes / no')) ?>">
            </div>
            <div data-qhide="disease_history,death_cause">
                <label for="health_event_type"><?= t('Zaznamenat jako zdravotní událost') ?></label>
                <select id="health_event_type" name="health_event_type">
                    <option value=""><?= t('- ne -') ?></option>
                    <?php foreach (\App\Repositories\HealthEventRepository::TYPES as $t): ?>
                        <option value="<?= e($t) ?>"<?= $healthEvent === $t ? ' selected' : '' ?>><?= e(\App\Support\HealthEventType::label($t)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn--primary"><?= t('Uložit otázku') ?></button>
        <a class="btn" href="/admin/forms/<?= $defId ?>"><?= t('Zrušit') ?></a>
    </form>
    <script src="<?= e(asset('assets/form-builder.js')) ?>"></script>
</div>
