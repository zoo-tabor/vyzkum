<?php
/** @var array<int, array<string, mixed>> $samples */
/** @var int|null $currentBreedId */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Vzorky</h1>
    <span>
        <a class="btn" href="/admin/batches">Dávky</a>
        <a class="btn" href="/admin/vets">Veterináři</a>
        <a class="btn" href="/admin/samples/export.csv">Export CSV</a>
        <a class="btn" href="/admin/samples/manual">+ Ruční vzorek</a>
        <a class="btn btn--primary" href="/admin/samples/new-batch">+ Nová dávka</a>
    </span>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <?php if ($samples === []): ?>
        <p class="muted">Žádné vzorky.</p>
    <?php else: ?>
        <table class="table" data-datatable data-per-page="25" data-per-page-options="25,50,100,all">
            <thead>
            <tr>
                <th>Sample ID</th>
                <th>Plemeno</th>
                <th>Pes</th>
                <th>Veterinář</th>
                <th>Odběr</th>
                <th>DNA izol.</th>
                <th>GWAS</th>
                <th>Stav</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($samples as $s): ?>
                <tr>
                    <td><a href="/admin/samples/<?= e(rawurlencode($s['sample_id'])) ?>"><code><?= e($s['sample_id']) ?></code></a></td>
                    <td><?= e($s['breed_name'] ?? '') ?: '-' ?></td>
                    <td><?= e($s['dog_name'] ?? '') ?: '-' ?></td>
                    <td><?= e($s['vet_name'] ?? '') ?: '-' ?></td>
                    <td data-sort="<?= e(substr((string) ($s['collection_date'] ?? ''), 0, 10)) ?>"><?= e(\App\Support\Dates::toCz($s['collection_date'] ?? null)) ?></td>
                    <td data-sort="<?= e(substr((string) ($s['dna_isolated_at'] ?? ''), 0, 10)) ?>"><?= e(\App\Support\Dates::toCz($s['dna_isolated_at'] ?? null)) ?: '-' ?></td>
                    <td><?= e(\App\Support\Gwas::label($s['gwas_status'] ?? null)) ?></td>
                    <td><?= e($s['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="muted">Řazení: šipky ↑/↓ v záhlaví. Filtr sloupce (např. Stav): ikona ⌕. Nahoře vpravo hledání (i podle jména psa) a počet záznamů.</p>
    <?php endif; ?>
</div>

<script src="<?= e(asset('assets/datatable.js')) ?>"></script>
