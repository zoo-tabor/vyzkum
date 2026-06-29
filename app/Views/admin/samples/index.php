<?php
/** @var array<int, array<string, mixed>> $samples */
/** @var string $status */
/** @var array<int, string> $statuses */
/** @var int|null $currentBreedId */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head" style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Vzorky</h1>
    <span>
        <a class="btn" href="/admin/batches">Davky</a>
        <a class="btn" href="/admin/vets">Veterinari</a>
        <a class="btn btn--primary" href="/admin/samples/new-batch">+ Nova davka</a>
    </span>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<form method="get" action="/admin/samples" class="card filters">
    <select name="status">
        <option value="">Stav: vse</option>
        <?php foreach ($statuses as $s): ?>
            <option value="<?= e($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Filtrovat</button>
</form>

<div class="card">
    <?php if ($samples === []): ?>
        <p class="muted">Zadne vzorky.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Sample ID</th><th>Plemeno</th><th>Pes</th><th>Veterinar</th><th>Odber</th><th>Stav</th></tr></thead>
            <tbody>
            <?php foreach ($samples as $s): ?>
                <tr>
                    <td><a href="/admin/samples/<?= e(rawurlencode($s['sample_id'])) ?>"><code><?= e($s['sample_id']) ?></code></a></td>
                    <td><?= e($s['breed_name'] ?? '') ?></td>
                    <td><?= e($s['dog_name'] ?? '') ?: '<span class="muted">-</span>' ?></td>
                    <td><?= e($s['vet_name'] ?? '') ?: '<span class="muted">-</span>' ?></td>
                    <td><?= e(\App\Support\Dates::toCz($s['collection_date'] ?? null)) ?></td>
                    <td><?= e($s['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
