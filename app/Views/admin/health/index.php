<?php
/** @var int|null $breedId */
/** @var array<int, array{event_type:string, c:int}> $byType */
/** @var array<int, array{normalized_code:string, c:int}> $diseases */
/** @var array<int, array{normalized_code:string, c:int}> $examinations */
/** @var array<string, string> $causeLabels kod => nazev nemoci (prelozeny) */
/** @var array<int, array<string, mixed>> $recent */
?>
<div class="page-head"><h1><?= t('Zdraví') ?></h1></div>

<?php if ($breedId === null): ?>
    <div class="card"><p class="muted"><?= t('Vyberte plemeno v přepínači nahoře pro zobrazení zdravotních statistik.') ?></p></div>
<?php else: ?>
    <div class="card">
        <h2><?= t('Četnost typů událostí') ?></h2>
        <?php if ($byType === []): ?><p class="muted"><?= t('Žádné zdravotní události.') ?></p><?php else: ?>
            <table class="table"><thead><tr><th><?= t('Typ') ?></th><th><?= t('Počet') ?></th></tr></thead><tbody>
                <?php foreach ($byType as $r): ?><tr><td><?= e(\App\Support\HealthEventType::label($r['event_type'])) ?></td><td><?= (int) $r['c'] ?></td></tr><?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </div>

    <div class="cards">
        <div class="card">
            <h2><?= t('Nemoci') ?></h2>
            <?php if ($diseases === []): ?><p class="muted">-</p><?php else: ?>
                <table class="table"><thead><tr><th><?= t('Nemoc / kód') ?></th><th><?= t('Počet') ?></th></tr></thead><tbody>
                    <?php foreach ($diseases as $r): ?>
                        <?php $code = (string) $r['normalized_code']; $lbl = $causeLabels[$code] ?? null; ?>
                        <tr>
                            <td><?php if ($lbl !== null): ?><?= e($lbl) ?> <span class="muted"><?= e($code) ?></span><?php else: ?><?= e($code) ?><?php endif; ?></td>
                            <td><?= (int) $r['c'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        </div>
        <div class="card">
            <h2><?= t('Vyšetření') ?></h2>
            <?php if ($examinations === []): ?><p class="muted">-</p><?php else: ?>
                <table class="table"><tbody>
                    <?php foreach ($examinations as $r): ?><tr><td><?= e($r['normalized_code']) ?></td><td><?= (int) $r['c'] ?></td></tr><?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h2><?= t('Poslední události') ?></h2>
        <?php if ($recent === []): ?><p class="muted"><?= t('Žádné.') ?></p><?php else: ?>
            <table class="table">
                <thead><tr><th><?= t('Pes') ?></th><th><?= t('Typ') ?></th><th><?= t('Kód') ?></th><th><?= t('Datum') ?></th><th><?= t('Zdroj') ?></th></tr></thead>
                <tbody>
                <?php foreach ($recent as $h): ?>
                    <tr>
                        <td><a href="/admin/dogs/<?= (int) $h['dog_id'] ?>"><?= e($h['dog_name']) ?></a></td>
                        <td><?= e(\App\Support\HealthEventType::label($h['event_type'])) ?></td>
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
