<?php
/** @var array<int, array<string, mixed>> $owners */
/** @var string|null $notice */
?>
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
    <h1><?= t('Majitelé') ?></h1>
    <a class="btn btn--primary" href="/admin/owners/new">+ <?= t('Nový majitel') ?></a>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>

<div class="card">
    <?php if ($owners === []): ?>
        <p class="muted"><?= t('Žádní majitelé.') ?></p>
    <?php else: ?>
        <table class="table" data-datatable data-per-page="25" data-per-page-options="25,50,100,all">
            <thead>
            <tr>
                <th><?= t('Jméno') ?></th>
                <th><?= t('Primární e-mail') ?></th>
                <th><?= t('Tel. číslo') ?></th>
                <th data-type="num"><?= t('Psi') ?></th>
                <th><?= t('Registrován') ?></th>
                <th><?= t('Jazyk') ?></th>
                <th><?= t('Aktualizace') ?></th>
                <th data-nofilter><?= t('Poznámka') ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($owners as $o): ?>
                <?php
                $lastAct = (string) ($o['last_activity'] ?? '');
                $hasLast = $lastAct !== '' && substr($lastAct, 0, 4) !== '1000';
                $lastSort = $hasLast ? substr($lastAct, 0, 10) : '';
                $lastActShow = $hasLast ? \App\Support\Dates::toCz(substr($lastAct, 0, 10)) : '-';
                $note = trim((string) ($o['note'] ?? ''));
                ?>
                <tr>
                    <td><a href="/admin/owners/<?= (int) $o['id'] ?>"><?= e($o['display_name']) ?></a></td>
                    <td><?= e($o['primary_email'] ?? '') ?: '-' ?></td>
                    <td><?= e(\App\Support\Phone::formatCz($o['primary_phone'] ?? null)) ?: '-' ?></td>
                    <td data-sort="<?= (int) $o['dog_count'] ?>"><?= (int) $o['dog_count'] ?></td>
                    <td><?= ((int) ($o['registered'] ?? 0)) === 1 ? t('Ano') : t('Ne') ?></td>
                    <td><?= !empty($o['language']) ? e(\App\Support\I18n::name((string) $o['language'])) : '-' ?></td>
                    <td data-sort="<?= e($lastSort) ?>"><?= e($lastActShow) ?></td>
                    <td><?= $note !== '' ? e(mb_strimwidth($note, 0, 40, '…')) : '-' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="muted"><?= t('Řazení: šipky ↑/↓ v záhlaví. Filtr sloupce: ikona ⌕. Nahoře vpravo hledání a počet záznamů.') ?></p>
    <?php endif; ?>
</div>

<script src="<?= e(asset('assets/datatable.js')) ?>"></script>
