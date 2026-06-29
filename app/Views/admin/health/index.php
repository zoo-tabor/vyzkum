<?php
/** @var int|null $breedId */
/** @var array<int, array{event_type:string, c:int}> $byType */
/** @var array<int, array{normalized_code:string, c:int}> $diseases */
/** @var array<int, array{normalized_code:string, c:int}> $examinations */
/** @var array<int, array<string, mixed>> $recent */
?>
<div class="page-head"><h1>Zdravi</h1></div>

<?php if ($breedId === null): ?>
    <div class="card"><p class="muted">Vyberte plemeno v prepinaci nahore pro zobrazeni zdravotnich statistik.</p></div>
<?php else: ?>
    <div class="card">
        <h2>Cetnost typu udalosti</h2>
        <?php if ($byType === []): ?><p class="muted">Zadne zdravotni udalosti.</p><?php else: ?>
            <table class="table"><thead><tr><th>Typ</th><th>Pocet</th></tr></thead><tbody>
                <?php foreach ($byType as $r): ?><tr><td><?= e($r['event_type']) ?></td><td><?= (int) $r['c'] ?></td></tr><?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </div>

    <div class="cards">
        <div class="card">
            <h2>Nemoci</h2>
            <?php if ($diseases === []): ?><p class="muted">-</p><?php else: ?>
                <table class="table"><tbody>
                    <?php foreach ($diseases as $r): ?><tr><td><?= e($r['normalized_code']) ?></td><td><?= (int) $r['c'] ?></td></tr><?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        </div>
        <div class="card">
            <h2>Vysetreni</h2>
            <?php if ($examinations === []): ?><p class="muted">-</p><?php else: ?>
                <table class="table"><tbody>
                    <?php foreach ($examinations as $r): ?><tr><td><?= e($r['normalized_code']) ?></td><td><?= (int) $r['c'] ?></td></tr><?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h2>Posledni udalosti</h2>
        <?php if ($recent === []): ?><p class="muted">Zadne.</p><?php else: ?>
            <table class="table">
                <thead><tr><th>Pes</th><th>Typ</th><th>Kod</th><th>Datum</th><th>Zdroj</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $h): ?>
                    <tr>
                        <td><a href="/admin/dogs/<?= (int) $h['dog_id'] ?>"><?= e($h['dog_name']) ?></a></td>
                        <td><?= e($h['event_type']) ?></td>
                        <td><?= e($h['normalized_code'] ?? '') ?></td>
                        <td><?= e(\App\Support\Dates::toCz($h['event_date'] ?? null)) ?></td>
                        <td><?= e($h['source_type']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>
