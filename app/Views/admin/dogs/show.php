<?php
/** @var array<string, mixed> $dog */
/** @var array<string, mixed>|null $currentOwner */
/** @var array<int, array<string, mixed>> $history */
/** @var string|null $notice */

$row = static function (string $label, mixed $value): void {
    echo '<tr><th style="width:200px">' . e($label) . '</th><td>' . e((string) ($value ?? '')) . '</td></tr>';
};
?>
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
    <h1><?= e($dog['name']) ?> <span class="muted">/ <?= e($dog['breed_name']) ?></span></h1>
    <a class="btn" href="/admin/dogs/<?= (int) $dog['id'] ?>/edit">Upravit</a>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>

<div class="card">
    <h2>Základní údaje</h2>
    <table class="table">
        <?php
        $row('Chovná stanice', $dog['kennel_name']);
        $row('Číslo čipu', $dog['chip_number']);
        $row('Číslo průkazu', $dog['pedigree_number']);
        $row('Pohlaví', $dog['sex']);
        $row('Datum narození', $dog['birth_date']);
        $row('Barva', $dog['color']);
        $row('Testovací skupina', $dog['test_group']);
        $row('Datum úmrtí', $dog['death_date']);
        $row('Příčina úmrtí', $dog['death_cause']);
        $row('Stav', empty($dog['death_date']) ? 'živý' : 'uhynulý');
        ?>
    </table>
    <?php if (!empty($dog['health_summary'])): ?>
        <p><strong>Zdravotní shrnutí:</strong><br><?= nl2br(e($dog['health_summary'])) ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Aktuální majitel</h2>
    <?php if ($currentOwner !== null): ?>
        <p><a href="/admin/owners/<?= (int) $currentOwner['id'] ?>"><?= e($currentOwner['display_name']) ?></a></p>
    <?php else: ?>
        <p class="muted">Pes nemá přiřazeného majitele.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Historie majitelů</h2>
    <?php if ($history === []): ?>
        <p class="muted">Zatím bez záznamu.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Majitel</th><th>Stav</th><th>Od</th><th>Do</th><th>Zdroj</th></tr></thead>
            <tbody>
            <?php foreach ($history as $h): ?>
                <tr>
                    <td><a href="/admin/owners/<?= (int) $h['id'] ?>"><?= e($h['display_name']) ?></a></td>
                    <td><?= ((int) $h['is_current']) === 1 ? 'aktuální' : 'bývalý' ?></td>
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
    <h2>Zdravotní události</h2>
    <?php if (empty($healthEvents)): ?>
        <p class="muted">Žádné zdravotní události.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Typ</th><th>Kód</th><th>Datum</th><th>Zdroj</th><th>Poznámka</th></tr></thead>
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
    <h2>Genetika</h2>
    <?php if (empty($genotypes)): ?>
        <p class="muted">Žádné genotypy.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Gen / marker</th><th>Genotyp</th><th>Datum testu</th><th>Stav</th></tr></thead>
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
    <h2>Dotazníky (odpovědi)</h2>
    <?php if (empty($responses)): ?>
        <p class="muted">Zatím žádné odeslané dotazníky.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Dotazník</th><th>Verze</th><th>Odesláno</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($responses as $r): ?>
                <tr>
                    <td><?= e($r['form_name']) ?></td>
                    <td>v<?= (int) $r['version'] ?></td>
                    <td><?= e(\App\Support\Dates::toCz(substr((string) $r['submitted_at'], 0, 10))) ?></td>
                    <td><a href="/admin/forms/responses/<?= (int) $r['id'] ?>">Zobrazit</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<p><a href="/admin/dogs">&larr; Zpět na seznam</a></p>
