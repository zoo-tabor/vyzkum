<?php
/** @var array<string, mixed> $sample */
/** @var array<int, string> $statuses */
/** @var string|null $notice */

$row = static function (string $label, mixed $value): void {
    echo '<tr><th style="width:220px">' . e($label) . '</th><td>' . e((string) ($value ?? '')) . '</td></tr>';
};
?>
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
    <div>
        <h1><?= t('Vzorek') ?> <code><?= e($sample['sample_id']) ?></code></h1>
        <p><a href="/admin/samples">&larr; <?= t('Zpět na vzorky') ?></a></p>
    </div>
    <a class="btn" href="/admin/samples/<?= e(rawurlencode($sample['sample_id'])) ?>/edit"><?= t('Upravit') ?></a>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>

<div class="card">
    <h2><?= t('Údaje vzorku') ?></h2>
    <table class="table">
        <?php
        $row(t('Stav'), \App\Support\SampleStatus::label($sample['status']));
        $row(t('Plemeno'), \App\Support\Breeds::translate($sample['breed_name']));
        $row(t('Veterinář'), trim((string) ($sample['vet_name'] ?? '') . (($sample['clinic_name'] ?? '') ? ' / ' . $sample['clinic_name'] : '')));
        $row(t('Číslo čipu (vet)'), $sample['chip_number_vet']);
        $row(t('Typ vzorku'), $sample['sample_type']);
        $row(t('Počet zkumavek'), $sample['material_count']);
        $row(t('Datum odběru'), \App\Support\Dates::toCz($sample['collection_date'] ?? null));
        $row(t('Vet odesláno'), $sample['vet_submitted_at']);
        $row(t('Majitel odesláno'), $sample['owner_submitted_at']);
        $row(t('Datum izolace DNA'), \App\Support\Dates::toCz($sample['dna_isolated_at'] ?? null));
        $row(t('GWAS'), \App\Support\Gwas::label($sample['gwas_status'] ?? null));
        $row(t('Poznámka'), $sample['note']);
        ?>
        <tr><th><?= t('Pes') ?></th><td>
            <?php if (!empty($sample['dog_id'])): ?>
                <a href="/admin/dogs/<?= (int) $sample['dog_id'] ?>"><?= e($sample['dog_name']) ?></a>
            <?php else: ?><span class="muted"><?= t('ještě neregistrován majitelem') ?></span><?php endif; ?>
        </td></tr>
    </table>
</div>

<div class="card">
    <h2><?= t('Změna stavu') ?></h2>
    <form method="post" action="/admin/samples/<?= e(rawurlencode($sample['sample_id'])) ?>/status">
        <?= \App\Core\Csrf::field() ?>
        <select name="status">
            <?php foreach ($statuses as $s): ?>
                <option value="<?= e($s) ?>"<?= $sample['status'] === $s ? ' selected' : '' ?>><?= e(\App\Support\SampleStatus::label($s)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn--primary"><?= t('Uložit stav') ?></button>
    </form>
</div>

<div class="card">
    <h2><?= t('Smazat vzorek') ?></h2>
    <form method="post" action="/admin/samples/<?= e(rawurlencode($sample['sample_id'])) ?>/delete"
          onsubmit="return confirm(<?= e(json_encode(t('Opravdu nevratně smazat vzorek {id}?', ['id' => $sample['sample_id']]), JSON_UNESCAPED_UNICODE)) ?>);">
        <?= \App\Core\Csrf::field() ?>
        <button type="submit" class="btn btn--danger"><?= t('Smazat vzorek') ?></button>
        <span class="muted"><?= t('Odstraní vzorek natrvalo. Genetická data psa zůstávají.') ?></span>
    </form>
</div>
