<?php
/** @var array<int, array<string, mixed>> $batches */
/** @var string|null $notice */
?>
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Dávky vzorků</h1>
    <a class="btn btn--primary" href="/admin/samples/new-batch">+ Nová dávka</a>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>

<div class="card">
    <?php if ($batches === []): ?>
        <p class="muted">Zatím žádné dávky.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>#</th><th>Popis</th><th>Plemeno</th><th>Veterinář</th><th>Sad</th><th>Vytvořeno</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($batches as $b): ?>
                <tr>
                    <td><?= (int) $b['id'] ?></td>
                    <td><?= e($b['label'] ?? '') ?></td>
                    <td><?= e($b['breed_name'] ?? '') ?></td>
                    <td><?= e($b['vet_name'] ?? '') ?></td>
                    <td><?= (int) $b['sample_count'] ?></td>
                    <td><?= e(\App\Support\Dates::toCz(substr((string) $b['created_at'], 0, 10))) ?></td>
                    <td><a href="/admin/batches/<?= (int) $b['id'] ?>/labels" target="_blank">Tisk štítků</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
