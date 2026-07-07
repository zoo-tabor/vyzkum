<?php
/** @var array<string, mixed> $dog */
/** @var array<string, mixed>|null $currentOwner */
/** @var array<int, array<string, mixed>> $history */
/** @var string|null $notice */

$row = static function (string $label, mixed $value): void {
    echo '<tr><th style="width:200px">' . e($label) . '</th><td>' . e((string) ($value ?? '')) . '</td></tr>';
};
$ageRef = \App\Support\Age::referenceDate($dog['death_date'] ?? null, $dog['alive_confirmed_at'] ?? null, $dog['newest_sample_received'] ?? null);
$age = \App\Support\Age::years($dog['birth_date'] ?? null, $ageRef);
?>
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
    <h1><?= e($dog['name']) ?> <span class="muted">/ <?= e($dog['breed_name']) ?></span></h1>
    <span style="display:flex; gap:0.5rem; align-items:center;">
        <a class="btn" href="/admin/dogs/<?= (int) $dog['id'] ?>/edit"><?= t('Upravit') ?></a>
        <form method="post" action="/admin/dogs/<?= (int) $dog['id'] ?>/delete" style="margin:0;"
              onsubmit="return confirm(<?= e(json_encode(t('Opravdu smazat psa „{name}“ a všechna jeho navázaná data (vzorky, genotypy, dotazníky, zprávy)? Tuto akci nelze vzít zpět.', ['name' => $dog['name']]), JSON_UNESCAPED_UNICODE)) ?>);">
            <?= \App\Core\Csrf::field() ?>
            <button type="submit" class="btn btn--danger"><?= t('Smazat') ?></button>
        </form>
    </span>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>

<div class="card">
    <h2><?= t('Základní údaje') ?></h2>
    <table class="table">
        <?php
        $row(t('Chovná stanice'), $dog['kennel_name']);
        $row(t('Číslo čipu'), $dog['chip_number']);
        $row(t('Číslo průkazu'), $dog['pedigree_number']);
        $row(t('Země původu'), \App\Support\Countries::name($dog['country'] ?? null));
        $row(t('Pohlaví'), $dog['sex']);
        $row(t('Datum narození'), \App\Support\Dates::toCz($dog['birth_date'] ?? null));
        $row(t('Věk'), $age !== null ? t('{count} let', ['count' => $age]) : '-');
        $row(t('Barva'), $dog['color']);
        $castLabel = match ((string) ($dog['castration_status'] ?? '')) {
            'intact' => t('nekastrovaný/á'),
            'castrated' => t('kastrovaný/á'),
            '' => '-',
            default => (string) $dog['castration_status'],
        };
        $row(t('Kastrace'), $castLabel);
        if (!empty($dog['castration_date'])) {
            $row(t('Datum kastrace'), \App\Support\Dates::toCz($dog['castration_date']));
        }
        $row(t('Testovací skupina'), $dog['test_group']);
        $row(t('Datum izolace DNA (nejnovější vzorek)'), \App\Support\Dates::toCz($dog['newest_dna_isolated_at'] ?? null));
        $row(t('GWAS (nejnovější vzorek)'), \App\Support\Gwas::label($dog['newest_gwas_status'] ?? null));
        $row(t('Datum úmrtí'), \App\Support\Dates::toCz($dog['death_date'] ?? null));
        $row(t('Příčina úmrtí'), $dog['death_cause']);
        if (!empty($dog['death_cause_note'])) {
            $row(t('Poznámka k příčině'), $dog['death_cause_note']);
        }
        $row(t('Stav'), empty($dog['death_date']) ? t('živý') : t('uhynulý'));
        ?>
    </table>
    <?php if (!empty($dog['health_summary'])): ?>
        <p><strong><?= t('Zdravotní shrnutí:') ?></strong><br><?= nl2br(e($dog['health_summary'])) ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <h2><?= t('Aktuální majitel') ?></h2>
    <?php if ($currentOwner !== null): ?>
        <p><a href="/admin/owners/<?= (int) $currentOwner['id'] ?>"><?= e($currentOwner['display_name']) ?></a></p>
    <?php else: ?>
        <p class="muted"><?= t('Pes nemá přiřazeného majitele.') ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <h2><?= t('Historie majitelů') ?></h2>
    <?php if ($history === []): ?>
        <p class="muted"><?= t('Zatím bez záznamu.') ?></p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th><?= t('Majitel') ?></th><th><?= t('Stav') ?></th><th><?= t('Od') ?></th><th><?= t('Do') ?></th><th><?= t('Zdroj') ?></th></tr></thead>
            <tbody>
            <?php foreach ($history as $h): ?>
                <tr>
                    <td><a href="/admin/owners/<?= (int) $h['id'] ?>"><?= e($h['display_name']) ?></a></td>
                    <td><?= ((int) $h['is_current']) === 1 ? t('aktuální') : t('bývalý') ?></td>
                    <td><?= e($h['valid_from'] ?? '') ?></td>
                    <td><?= e($h['valid_to'] ?? '') ?></td>
                    <td><?= e($h['source']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2><?= t('Zdravotní záznamy') ?></h2>
    <?php if (empty($healthEvents)): ?>
        <p class="muted"><?= t('Žádné zdravotní události.') ?></p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th><?= t('Typ') ?></th><th><?= t('Kód') ?></th><th><?= t('Datum') ?></th><th><?= t('Zdroj') ?></th><th><?= t('Poznámka') ?></th></tr></thead>
            <tbody>
            <?php foreach ($healthEvents as $h): ?>
                <tr>
                    <td><?= e($h['event_type']) ?></td>
                    <td><?= e($h['normalized_code'] ?? '') ?></td>
                    <td><?= e(\App\Support\Dates::toCz($h['event_date'] ?? null)) ?></td>
                    <td><?= e($h['source_type']) ?></td>
                    <td><?= e($h['note'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2><?= t('Genetika') ?></h2>
    <?php if (empty($genotypes)): ?>
        <p class="muted"><?= t('Žádné genotypy.') ?></p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th><?= t('Gen / marker') ?></th><th><?= t('Genotyp') ?></th><th><?= t('Datum testu') ?></th><th><?= t('Stav') ?></th></tr></thead>
            <tbody>
            <?php foreach ($genotypes as $g): ?>
                <tr>
                    <td><?= e($g['gene_symbol']) ?> / <code><?= e($g['marker_code']) ?></code></td>
                    <td><strong><?= e($g['genotype']) ?></strong></td>
                    <td><?= e(\App\Support\Dates::toCz($g['tested_at'] ?? null)) ?></td>
                    <td><?= e($g['validation_status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <h2><?= t('Dotazníky (odpovědi)') ?></h2>
    <?php if (empty($responses)): ?>
        <p class="muted"><?= t('Zatím žádné odeslané dotazníky.') ?></p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th><?= t('Dotazník') ?></th><th><?= t('Verze') ?></th><th><?= t('Odesláno') ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($responses as $r): ?>
                <tr>
                    <td><?= e($r['form_name']) ?></td>
                    <td>v<?= (int) $r['version'] ?></td>
                    <td><?= e(\App\Support\Dates::toCz(substr((string) $r['submitted_at'], 0, 10))) ?></td>
                    <td><a href="/admin/forms/responses/<?= (int) $r['id'] ?>"><?= t('Zobrazit') ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<p><a href="/admin/dogs">&larr; <?= t('Zpět na seznam') ?></a></p>
