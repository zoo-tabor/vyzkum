<?php
/** @var array<int, array<string, mixed>> $templates */
/** @var string|null $notice */
/** @var string|null $error */

// Popisky sablon (literaly kvuli extraktoru).
$label = static fn (string $k): string => match ($k) {
    'set_password' => t('Nastavení hesla (pozvánka)'),
    'password_reset' => t('Obnova hesla'),
    'ownership_transfer' => t('Převzetí psa (převod vlastnictví)'),
    'form_broadcast' => t('Rozeslání dotazníku'),
    default => $k,
};
?>
<div class="page-head">
    <h1><?= t('Šablony e-mailů') ?></h1>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p class="muted"><?= t('Text transakčních e-mailů. Český zdroj lze upravit a doplnit překlady pro ostatní jazyky - e-mail se pak odešle v jazyce příjemce (dle nastaveného jazyka majitele, jinak česky).') ?></p>
    <?php if ($templates === []): ?>
        <p class="muted"><?= t('Žádné šablony. Spusťte migraci ensure_schema.sql.') ?></p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th><?= t('Šablona') ?></th><th><?= t('Předmět (český zdroj)') ?></th><th><?= t('Značky') ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($templates as $tpl): ?>
                <tr>
                    <td><strong><?= e($label((string) $tpl['key'])) ?></strong><br><span class="muted"><code><?= e($tpl['key']) ?></code></span></td>
                    <td><?= e($tpl['subject']) ?></td>
                    <td><?php if (!empty($tpl['placeholders'])): ?><code><?= e($tpl['placeholders']) ?></code><?php endif; ?></td>
                    <td><a class="btn btn--ghost" href="/admin/email-templates/<?= e(rawurlencode((string) $tpl['key'])) ?>"><?= t('Upravit') ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
