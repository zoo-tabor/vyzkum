<?php
/** @var array<int, array<string, mixed>> $breeds */
/** @var int $breedId */
/** @var array<int, array<string, mixed>> $nodes */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head">
    <h1><?= t('Příčiny úmrtí') ?></h1>
    <p class="muted"><?= t('Číselník příčin úmrtí pro každé plemeno. Kategorie a nemoci lze přidávat, upravovat, mazat a řadit. Používá se při hlášení úmrtí i ve zdravotní historii.') ?></p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <form method="get" action="/admin/death-causes" class="form-row">
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
        <div style="align-self:end">
            <noscript><button class="btn" type="submit"><?= t('Zobrazit') ?></button></noscript>
            <?php if ($breedId > 0): ?>
                <a class="btn" href="/admin/death-causes/translations?breed=<?= $breedId ?>"><?= t('Překlady') ?></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($breedId > 0): ?>
<div class="card">
    <h2><?= t('Strom příčin') ?> (<?= count($nodes) ?>)</h2>
    <?php if ($nodes === []): ?>
        <p class="muted"><?= t('Pro toto plemeno zatím není žádný číselník. Přidejte první položku níže.') ?></p>
    <?php else: ?>
        <table class="table">
            <thead><tr>
                <th><?= t('Příčina') ?></th>
                <th><?= t('Poznámka') ?></th>
                <th><?= t('Akce') ?></th>
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
                    <td><?= ((int) $n['has_note']) === 1 ? t('ano') : '<span class="muted">' . t('ne') . '</span>' ?></td>
                    <td style="white-space:nowrap">
                        <form method="post" action="/admin/death-causes/<?= $nid ?>/move" class="inline">
                            <?= \App\Core\Csrf::field() ?><input type="hidden" name="dir" value="up"><button class="btn btn--ghost" type="submit" title="<?= e(t('Nahoru')) ?>">&uarr;</button>
                        </form>
                        <form method="post" action="/admin/death-causes/<?= $nid ?>/move" class="inline">
                            <?= \App\Core\Csrf::field() ?><input type="hidden" name="dir" value="down"><button class="btn btn--ghost" type="submit" title="<?= e(t('Dolů')) ?>">&darr;</button>
                        </form>
                        <a class="btn btn--ghost" href="/admin/death-causes/<?= $nid ?>/edit"><?= t('Upravit') ?></a>
                        <form method="post" action="/admin/death-causes/<?= $nid ?>/delete" class="inline"
                              onsubmit="return confirm(<?= e(json_encode(t('Smazat příčinu?'), JSON_UNESCAPED_UNICODE)) ?>);">
                            <?= \App\Core\Csrf::field() ?><button class="btn btn--ghost" type="submit"><?= t('Smazat') ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2><?= t('Přidat příčinu') ?></h2>
    <form method="post" action="/admin/death-causes">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="breed" value="<?= $breedId ?>">

        <label for="label"><?= t('Název příčiny') ?> *</label>
        <input type="text" id="label" name="label" required>

        <div class="form-row">
            <div>
                <label for="parent_id"><?= t('Nadřízená kategorie') ?></label>
                <select id="parent_id" name="parent_id">
                    <option value=""><?= t('- kořenová (nejvyšší úroveň) -') ?></option>
                    <?php foreach ($nodes as $n): ?>
                        <option value="<?= (int) $n['id'] ?>"><?= str_repeat('&nbsp;&nbsp;', (int) $n['depth']) ?><?= e((string) $n['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>&nbsp;</label>
                <label class="inline"><input type="checkbox" name="has_note" value="1"> <?= t('Umožnit vlastní poznámku (např. „Jiné…“)') ?></label>
            </div>
        </div>

        <button type="submit" class="btn btn--primary"><?= t('Přidat příčinu') ?></button>
    </form>
</div>
<?php endif; ?>
