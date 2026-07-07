<?php
/** @var array<int, array<string, mixed>> $responses */
/** @var string|null $notice */
?>
<div class="page-head"><h1><?= t('Dotazníky') ?></h1></div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>

<div class="card">
    <p class="muted"><?= t('Přehled vyplněných dotazníků k vašim psům (jen k nahlédnutí). Nové dotazníky vyplníte v detailu psa.') ?></p>
    <?php if ($responses === []): ?>
        <p class="muted"><?= t('Zatím nemáte žádné vyplněné dotazníky.') ?></p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th><?= t('Pes') ?></th><th><?= t('Dotazník') ?></th><th><?= t('Odesláno') ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($responses as $r): ?>
                <tr>
                    <td><?= e($r['dog_name']) ?></td>
                    <td><?= e($r['form_name']) ?> <span class="muted">v<?= (int) $r['version'] ?></span></td>
                    <td><?= e(\App\Support\Dates::toCzDateTime((string) $r['submitted_at'])) ?></td>
                    <td><a href="/portal/forms/<?= (int) $r['id'] ?>"><?= t('Zobrazit') ?> &rarr;</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
