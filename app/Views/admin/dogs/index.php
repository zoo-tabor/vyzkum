<?php
/** @var array<int, array<string, mixed>> $dogs */
/** @var int|null $currentBreedId */
/** @var array<int, array{id:int, symbol:string}> $genes */
/** @var array<int, array<int, array<string, mixed>>> $samplesByDog */
/** @var array<int, array<int, string>> $genotypesByDog */
/** @var string|null $notice */

use App\Support\Age;
use App\Support\Countries;
use App\Support\Dates;
?>
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Psi</h1>
    <span>
        <a class="btn" href="/admin/import">Import CSV</a>
        <a class="btn" href="/admin/dogs/export.csv">Export CSV</a>
        <a class="btn btn--primary" href="/admin/dogs/new">+ Nový pes</a>
    </span>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if ($currentBreedId === null): ?>
    <p class="muted">Zobrazuji všechna plemena. Pro sloupce genotypů vyberte konkrétní plemeno v přepínači nahoře.</p>
<?php endif; ?>

<div class="card">
    <?php if ($dogs === []): ?>
        <p class="muted">Žádní psi.</p>
    <?php else: ?>
        <table class="table table--dogs" data-datatable data-per-page="25" data-per-page-options="25,50,100,all">
            <thead>
            <tr>
                <th>Jméno</th>
                <th>Plemeno</th>
                <th>Pohlaví</th>
                <th data-type="num">Věk</th>
                <th>Země</th>
                <th data-nofilter>Vzorky</th>
                <th>DNA izol.</th>
                <?php foreach ($genes as $g): ?>
                    <th><?= e($g['symbol']) ?></th>
                <?php endforeach; ?>
                <th>GWAS</th>
                <th>Majitel</th>
                <th>Stav</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($dogs as $d): ?>
                <?php
                $ref = Age::referenceDate($d['death_date'] ?? null, $d['alive_confirmed_at'] ?? null, $d['newest_sample_received'] ?? null);
                $age = Age::years($d['birth_date'] ?? null, $ref);
                $dogSamples = $samplesByDog[(int) $d['id']] ?? [];
                $dogGenos = $genotypesByDog[(int) $d['id']] ?? [];
                ?>
                <tr>
                    <td class="col-name"><a href="/admin/dogs/<?= (int) $d['id'] ?>"><?= e($d['name']) ?></a>
                        <?php if (!empty($d['chip_number'])): ?><br><span class="muted"><?= e($d['chip_number']) ?></span><?php endif; ?>
                    </td>
                    <td><?= e($d['breed_name']) ?></td>
                    <td><?= e($d['sex']) ?></td>
                    <td data-sort="<?= $age ?? -1 ?>"><?= $age !== null ? (int) $age : '-' ?></td>
                    <td title="<?= e(Countries::name($d['country'] ?? null) ?? '') ?>"><?= e($d['country'] ?? '') ?: '-' ?></td>
                    <td>
                        <?php if ($dogSamples === []): ?><span class="muted">-</span><?php else: ?>
                            <?php foreach ($dogSamples as $s): ?>
                                <div><code><?= e($s['sample_id']) ?></code>
                                    <?php if (!empty($s['received_at'])): ?><span class="muted">(<?= e(Dates::toCz(substr((string) $s['received_at'], 0, 10))) ?>)</span><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td data-sort="<?= e($d['dna_isolated_at'] ?? '') ?>"><?= e(Dates::toCz($d['dna_isolated_at'] ?? null)) ?: '-' ?></td>
                    <?php foreach ($genes as $g): ?>
                        <td><?= e($dogGenos[$g['id']] ?? '') ?: '-' ?></td>
                    <?php endforeach; ?>
                    <td><?= e($d['gwas_status'] ?? '') ?: '-' ?></td>
                    <td>
                        <?php if (!empty($d['owner_id'])): ?>
                            <a href="/admin/owners/<?= (int) $d['owner_id'] ?>"><?= e($d['owner_name']) ?></a>
                        <?php else: ?><span class="muted">-</span><?php endif; ?>
                    </td>
                    <td><?= empty($d['death_date']) ? 'živý' : 'uhynulý' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="muted">Řazení: klik na záhlaví sloupce (A→Z / Z→A). Filtr sloupce: ikona ⌕ v záhlaví.</p>
    <?php endif; ?>
</div>

<script src="<?= e(asset('assets/datatable.js')) ?>"></script>
