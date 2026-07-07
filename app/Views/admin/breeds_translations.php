<?php
/** @var array<string, mixed> $breed */
/** @var array<int, string> $targetLocales */
/** @var array<string, string> $existing */
/** @var string|null $error */

use App\Support\I18n;

$breedId = (int) $breed['id'];
?>
<div class="page-head">
    <h1><?= t('Překlady plemene:') ?> <?= e($breed['name']) ?></h1>
    <p><a href="/admin/breeds">&larr; <?= t('Zpět na plemena') ?></a></p>
</div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p class="muted"><?= t('Vlevo je český zdroj, vpravo doplňte překlad názvu. Prázdné pole = zobrazí se čeština. Slug se nepřekládá.') ?></p>

    <form method="post" action="/admin/breeds/<?= $breedId ?>/translations">
        <?= \App\Core\Csrf::field() ?>

        <table class="table">
            <thead><tr>
                <th style="width:35%"><?= t('Jazyk') ?></th>
                <th><?= t('Překlad názvu') ?></th>
            </tr></thead>
            <tbody>
                <tr>
                    <td><strong><?= e(I18n::name(I18n::defaultLocale())) ?></strong> <span class="muted">(<?= t('zdroj') ?>)</span></td>
                    <td><?= e($breed['name']) ?></td>
                </tr>
                <?php foreach ($targetLocales as $code): ?>
                    <tr>
                        <td>
                            <img src="<?= e(asset('assets/flags/' . I18n::flag($code) . '.svg')) ?>" alt="" width="20" height="15" style="vertical-align:-2px;margin-right:.35rem">
                            <?= e(I18n::name($code)) ?>
                        </td>
                        <td><input type="text" name="name[<?= e($code) ?>]" value="<?= e($existing[$code] ?? '') ?>"></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" class="btn btn--primary"><?= t('Uložit překlady') ?></button>
        <a class="btn" href="/admin/breeds"><?= t('Zrušit') ?></a>
    </form>
</div>
