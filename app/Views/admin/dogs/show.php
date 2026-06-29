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
    <h2>Zakladni udaje</h2>
    <table class="table">
        <?php
        $row('Chovna stanice', $dog['kennel_name']);
        $row('Cislo cipu', $dog['chip_number']);
        $row('Cislo prukazu', $dog['pedigree_number']);
        $row('Pohlavi', $dog['sex']);
        $row('Datum narozeni', $dog['birth_date']);
        $row('Barva', $dog['color']);
        $row('Testovaci skupina', $dog['test_group']);
        $row('Datum umrti', $dog['death_date']);
        $row('Pricina umrti', $dog['death_cause']);
        $row('Stav', empty($dog['death_date']) ? 'zivy' : 'uhynuly');
        ?>
    </table>
    <?php if (!empty($dog['health_summary'])): ?>
        <p><strong>Zdravotni shrnuti:</strong><br><?= nl2br(e($dog['health_summary'])) ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Aktualni majitel</h2>
    <?php if ($currentOwner !== null): ?>
        <p><a href="/admin/owners/<?= (int) $currentOwner['id'] ?>"><?= e($currentOwner['display_name']) ?></a></p>
    <?php else: ?>
        <p class="muted">Pes nema prirazeneho majitele.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Historie majitelu</h2>
    <?php if ($history === []): ?>
        <p class="muted">Zatim bez zaznamu.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Majitel</th><th>Stav</th><th>Od</th><th>Do</th><th>Zdroj</th></tr></thead>
            <tbody>
            <?php foreach ($history as $h): ?>
                <tr>
                    <td><a href="/admin/owners/<?= (int) $h['id'] ?>"><?= e($h['display_name']) ?></a></td>
                    <td><?= ((int) $h['is_current']) === 1 ? 'aktualni' : 'byvaly' ?></td>
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
    <h2>Genetika</h2>
    <?php if (empty($genotypes)): ?>
        <p class="muted">Zadne genotypy.</p>
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
    <h2>Dotazniky (odpovedi)</h2>
    <?php if (empty($responses)): ?>
        <p class="muted">Zatim zadne odeslane dotazniky.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Dotaznik</th><th>Verze</th><th>Odeslano</th><th></th></tr></thead>
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

<p><a href="/admin/dogs">&larr; Zpet na seznam</a></p>
