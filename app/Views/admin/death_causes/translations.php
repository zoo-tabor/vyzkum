<?php
/** @var array<int, array<string, mixed>> $breeds */
/** @var int $breedId */
/** @var array<int, array<string, mixed>> $nodes */
/** @var array<int, string> $targetLocales */
/** @var string $lang */
/** @var array<int, array<string, string>> $tx */
/** @var string|null $notice */
/** @var string|null $error */

use App\Support\I18n;
?>
<div class="page-head">
    <h1><?= t('Překlady příčin úmrtí') ?></h1>
    <p><a href="/admin/death-causes?breed=<?= $breedId ?>">&larr; <?= t('Zpět na číselník') ?></a></p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <form method="get" action="/admin/death-causes/translations" class="form-row">
        <div>
            <label for="breed"><?= t('Plemeno') ?></label>
            <select id="breed" name="breed" onchange="this.form.submit()">
                <?php foreach ($breeds as $b): ?>
                    <option value="<?= (int) $b['id'] ?>" <?= (int) $b['id'] === $breedId ? 'selected' : '' ?>>
                        <?= e(\App\Support\Breeds::translate((string) $b['name'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" name="lang" value="<?= e($lang) ?>">
        <div style="align-self:end"><noscript><button class="btn" type="submit"><?= t('Zobrazit') ?></button></noscript></div>
    </form>
</div>

<?php if ($nodes === []): ?>
    <div class="card"><p class="muted"><?= t('Pro toto plemeno zatím není žádný číselník.') ?></p></div>
<?php else: ?>
<div class="card">
    <p class="muted"><?= t('Vlevo je český zdroj, vpravo doplňte překlad. Prázdné pole = zobrazí se čeština.') ?></p>

    <div class="lang-tabs" style="margin-bottom:1rem">
        <?php foreach ($targetLocales as $code): ?>
            <a class="btn <?= $code === $lang ? 'btn--primary' : 'btn--ghost' ?>"
               href="/admin/death-causes/translations?breed=<?= $breedId ?>&lang=<?= e($code) ?>">
                <img src="<?= e(asset('assets/flags/' . I18n::flag($code) . '.svg')) ?>" alt="" width="20" height="15" style="vertical-align:-2px;margin-right:.35rem">
                <?= e(I18n::name($code)) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <form method="post" action="/admin/death-causes/translations">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="breed" value="<?= $breedId ?>">
        <input type="hidden" name="lang" value="<?= e($lang) ?>">

        <table class="table">
            <thead><tr>
                <th style="width:50%"><?= t('Český zdroj') ?></th>
                <th><?= t('Překlad') ?> (<?= e(I18n::name($lang)) ?>)</th>
            </tr></thead>
            <tbody>
            <?php foreach ($nodes as $n): ?>
                <?php $nid = (int) $n['id']; $depth = (int) $n['depth']; ?>
                <tr>
                    <td style="padding-left:<?= 0.5 + $depth * 1.5 ?>rem">
                        <?php if ($depth > 0): ?><span class="muted">&#8735;</span> <?php endif; ?>
                        <?= e((string) $n['label']) ?>
                        <span class="muted"><code><?= e((string) $n['code']) ?></code></span>
                    </td>
                    <td><input type="text" name="label[<?= $nid ?>]" value="<?= e($tx[$nid]['label'] ?? '') ?>"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" class="btn btn--primary"><?= t('Uložit překlady ({lang})', ['lang' => e(I18n::name($lang))]) ?></button>
    </form>
</div>
<?php endif; ?>
