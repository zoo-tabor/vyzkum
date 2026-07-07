<?php
/** @var array<string, mixed> $def */
/** @var array<string, mixed>|null $draft */
/** @var array<string, mixed>|null $published */
/** @var array<string, mixed>|null $editing */
/** @var bool $canEdit */
/** @var array<int, array<string, mixed>> $questions */
/** @var array<int, array<int, array<string, mixed>>> $options */
/** @var array{total:int, completed:int, last_sent:?string} $assignmentStats */
/** @var string|null $notice */
/** @var string|null $error */

use App\Support\FormSchema;

$defId = (int) $def['id'];
?>
<div class="page-head">
    <h1><?= e($def['name']) ?> <span class="muted">/ <?= e($def['breed_name']) ?></span></h1>
    <p><a href="/admin/forms">&larr; <?= t('Zpět na seznam') ?></a></p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p>
        <?php if ($editing !== null): ?>
            <?= t('Upravovaná verze: {version}.', ['version' => '<strong>v' . (int) $editing['version'] . ' (' . e($editing['status']) . ')</strong>']) ?>
        <?php endif; ?>
        <?php if ($published !== null): ?>
            <?= t('Publikovaná verze: {version}.', ['version' => '<strong>v' . (int) $published['version'] . '</strong>']) ?>
        <?php else: ?>
            <span class="muted"><?= t('Zatím nepublikováno.') ?></span>
        <?php endif; ?>
    </p>
    <?php if ($canEdit): ?>
        <form method="post" action="/admin/forms/<?= $defId ?>/publish" class="inline"
              onsubmit="return confirm(<?= e(json_encode(t('Publikovat tuto verzi? Po publikaci už nepůjde měnit (vznikne nová verze až při další úpravě).'), JSON_UNESCAPED_UNICODE)) ?>);">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn--primary"><?= t('Publikovat verzi') ?></button>
        </form>
    <?php else: ?>
        <form method="post" action="/admin/forms/<?= $defId ?>/new-version" class="inline">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn"><?= t('Vytvořit novou verzi (pro úpravy)') ?></button>
        </form>
        <span class="muted"><?= t('Publikovaná verze je zamčená.') ?></span>
    <?php endif; ?>
</div>

<div class="card">
    <h2><?= t('Rozeslání majitelům') ?></h2>
    <?php if ($published !== null): ?>
        <p>
            <a class="btn btn--primary" href="/admin/forms/<?= $defId ?>/send"><?= t('Rozeslat dotazník') ?></a>
            <span class="muted"><?= t('Rozešle e-mail s odkazem všem majitelům žijících psů plemene {breed} (1 e-mail na psa).', ['breed' => '<strong>' . e($def['breed_name']) . '</strong>']) ?></span>
        </p>
        <?php if ($assignmentStats['total'] > 0): ?>
            <p class="muted">
                <?= t('Dosud rozesláno: {total} úkolů, vyplněno: {completed}.', ['total' => '<strong>' . (int) $assignmentStats['total'] . '</strong>', 'completed' => '<strong>' . (int) $assignmentStats['completed'] . '</strong>']) ?>
                <?php if (!empty($assignmentStats['last_sent'])): ?>
                    <?= t('Naposledy: {date}.', ['date' => e(\App\Support\Dates::toCz(substr((string) $assignmentStats['last_sent'], 0, 10)))]) ?>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    <?php else: ?>
        <p class="muted"><?= t('Dotazník lze rozeslat až po jeho publikaci.') ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <h2><?= t('Otázky') ?> (<?= count($questions) ?>)</h2>
    <?php if ($questions === []): ?>
        <p class="muted"><?= t('Zatím žádné otázky.') ?></p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>#</th><th><?= t('Otázka') ?></th><th><?= t('Typ') ?></th><th><?= t('Povinná') ?></th><th><?= t('Detail') ?></th><?php if ($canEdit): ?><th><?= t('Akce') ?></th><?php endif; ?></tr></thead>
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
                    <td><?= ((int) $q['is_required']) === 1 ? t('ano') : t('ne') ?></td>
                    <td>
                        <?php if ($qOptions !== []): ?>
                            <span class="muted"><?= t('možnosti:') ?> <?= e(implode(', ', array_map(static fn ($o) => $o['label'], $qOptions))) ?></span><br>
                        <?php endif; ?>
                        <?php if ($visibleIf !== null): ?>
                            <span class="muted"><?= t('zobrazit když {q} = {v}', ['q' => '<code>' . e($visibleIf['q']) . '</code>', 'v' => '<code>' . e($visibleIf['eq']) . '</code>']) ?></span>
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
                            <a class="btn btn--ghost" href="/admin/forms/<?= $defId ?>/questions/<?= (int) $q['id'] ?>/edit"><?= t('Upravit') ?></a>
                            <form method="post" action="/admin/forms/<?= $defId ?>/questions/<?= (int) $q['id'] ?>/delete" class="inline"
                                  onsubmit="return confirm(<?= e(json_encode(t('Smazat otázku?'), JSON_UNESCAPED_UNICODE)) ?>);">
                                <?= \App\Core\Csrf::field() ?><button class="btn btn--ghost" type="submit"><?= t('Smazat') ?></button>
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
        <h2><?= t('Přidat otázku') ?></h2>
        <form method="post" action="/admin/forms/<?= $defId ?>/questions">
            <?= \App\Core\Csrf::field() ?>

            <label for="label"><?= t('Text otázky') ?> *</label>
            <input type="text" id="label" name="label" required>

            <div class="form-row">
                <div>
                    <label for="type"><?= t('Typ') ?> *</label>
                    <select id="type" name="type" required>
                        <?php foreach (FormSchema::TYPES as $k => $lbl): ?>
                            <option value="<?= $k ?>"><?= e($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="help_text"><?= t('Nápověda (nepovinné)') ?></label>
                    <input type="text" id="help_text" name="help_text">
                </div>
                <div>
                    <label>&nbsp;</label>
                    <label class="inline"><input type="checkbox" name="is_required" value="1"> <?= t('Povinná') ?></label>
                </div>
            </div>

            <label for="options"><?= t('Možnosti (jen pro "volby") - jedna na řádek, volitelně {format}', ['format' => '<code>klíč|popisek</code>']) ?></label>
            <textarea id="options" name="options" rows="3" placeholder="Ano&#10;Ne"></textarea>

            <div class="form-row">
                <div>
                    <label for="visible_if_question"><?= t('Zobrazit jen když otázka (klíč)') ?></label>
                    <select id="visible_if_question" name="visible_if_question">
                        <option value=""><?= t('- vždy zobrazit -') ?></option>
                        <?php foreach ($questions as $q): ?>
                            <option value="<?= e($q['question_key']) ?>"><?= e($q['question_key']) ?> (<?= e($q['label']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="visible_if_value"><?= t('má hodnotu (klíč možnosti / "yes" / "no")') ?></label>
                    <input type="text" id="visible_if_value" name="visible_if_value">
                </div>
                <div>
                    <label for="health_event_type"><?= t('Zaznamenat jako zdravotní událost') ?></label>
                    <select id="health_event_type" name="health_event_type">
                        <option value=""><?= t('- ne -') ?></option>
                        <?php foreach (\App\Repositories\HealthEventRepository::TYPES as $t): ?>
                            <option value="<?= e($t) ?>"><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn--primary"><?= t('Přidat otázku') ?></button>
        </form>
        <p class="muted"><?= t('Příklad podmíněné otázky: "datum úmrtí" se zobrazí jen když otázka {q} = {v}.', ['q' => '<code>je_pes_nazivu</code>', 'v' => '<code>no</code>']) ?></p>
    </div>
<?php endif; ?>
