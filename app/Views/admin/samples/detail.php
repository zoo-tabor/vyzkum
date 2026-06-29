<?php
/** @var array<string, mixed> $sample */
/** @var array<int, string> $statuses */
/** @var string|null $notice */

$row = static function (string $label, mixed $value): void {
    echo '<tr><th style="width:220px">' . e($label) . '</th><td>' . e((string) ($value ?? '')) . '</td></tr>';
};
?>
<div class="page-head">
    <h1>Vzorek <code><?= e($sample['sample_id']) ?></code></h1>
    <p><a href="/admin/samples">&larr; Zpet na vzorky</a></p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>

<div class="card">
    <h2>Udaje vzorku</h2>
    <table class="table">
        <?php
        $row('Stav', $sample['status']);
        $row('Plemeno', $sample['breed_name']);
        $row('Veterinar', trim((string) ($sample['vet_name'] ?? '') . (($sample['clinic_name'] ?? '') ? ' / ' . $sample['clinic_name'] : '')));
        $row('Cislo cipu (vet)', $sample['chip_number_vet']);
        $row('Typ vzorku', $sample['sample_type']);
        $row('Pocet zkumavek', $sample['material_count']);
        $row('Datum odberu', \App\Support\Dates::toCz($sample['collection_date'] ?? null));
        $row('Vet odeslano', $sample['vet_submitted_at']);
        $row('Majitel odeslano', $sample['owner_submitted_at']);
        ?>
        <tr><th>Pes</th><td>
            <?php if (!empty($sample['dog_id'])): ?>
                <a href="/admin/dogs/<?= (int) $sample['dog_id'] ?>"><?= e($sample['dog_name']) ?></a>
            <?php else: ?><span class="muted">jeste neregistrovan majitelem</span><?php endif; ?>
        </td></tr>
    </table>
</div>

<div class="card">
    <h2>Zmena stavu</h2>
    <form method="post" action="/admin/samples/<?= e(rawurlencode($sample['sample_id'])) ?>/status">
        <?= \App\Core\Csrf::field() ?>
        <select name="status">
            <?php foreach ($statuses as $s): ?>
                <option value="<?= e($s) ?>"<?= $sample['status'] === $s ? ' selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn--primary">Ulozit stav</button>
    </form>
</div>
