<?php
/** @var array<int, array<string, mixed>> $genes */
/** @var array<int, array<string, mixed>> $markers */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head">
    <h1>Geny a markery</h1>
    <p><a href="/admin/genetics">&larr; Zpět na genotypy</a></p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Nový gen</h2>
    <form method="post" action="/admin/genetics/genes" class="form-row">
        <?= \App\Core\Csrf::field() ?>
        <div><label for="symbol">Symbol *</label><input type="text" id="symbol" name="symbol" placeholder="napr. B3GALNT1" required></div>
        <div><label for="name">Název</label><input type="text" id="name" name="name"></div>
        <div><label for="note">Poznámka</label><input type="text" id="note" name="note"></div>
        <div class="form-row__action"><button type="submit" class="btn btn--primary">Přidat gen</button></div>
    </form>
    <table class="table">
        <thead><tr><th>Symbol</th><th>Název</th><th>Poznámka</th><th>Markerů</th></tr></thead>
        <tbody>
        <?php foreach ($genes as $g): ?>
            <tr><td><code><?= e($g['symbol']) ?></code></td><td><?= e($g['name'] ?? '') ?></td><td><?= e($g['note'] ?? '') ?></td><td><?= (int) $g['marker_count'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h2>Nový marker</h2>
    <?php if ($genes === []): ?>
        <p class="muted">Nejdříve založte gen.</p>
    <?php else: ?>
        <form method="post" action="/admin/genetics/markers">
            <?= \App\Core\Csrf::field() ?>
            <div class="form-row">
                <div>
                    <label for="gene_id">Gen</label>
                    <select id="gene_id" name="gene_id" required>
                        <?php foreach ($genes as $g): ?>
                            <option value="<?= (int) $g['id'] ?>"><?= e($g['symbol']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><label for="marker_code">Kód markeru *</label><input type="text" id="marker_code" name="marker_code" placeholder="napr. B3GALNT1" required></div>
                <div><label for="locus">Lokus</label><input type="text" id="locus" name="locus"></div>
            </div>
            <div class="form-row">
                <div><label for="reference_allele">Ref. alela</label><input type="text" id="reference_allele" name="reference_allele"></div>
                <div><label for="alternate_allele">Alt. alela</label><input type="text" id="alternate_allele" name="alternate_allele"></div>
                <div><label for="allowed_values">Povolené hodnoty</label><input type="text" id="allowed_values" name="allowed_values" placeholder="GG,GC,CC"></div>
            </div>
            <button type="submit" class="btn btn--primary">Přidat marker</button>
        </form>
    <?php endif; ?>

    <table class="table" style="margin-top:1rem">
        <thead><tr><th>Marker</th><th>Gen</th><th>Lokus</th><th>Ref/Alt</th><th>Povolené</th></tr></thead>
        <tbody>
        <?php foreach ($markers as $m): ?>
            <tr>
                <td><code><?= e($m['marker_code']) ?></code></td>
                <td><?= e($m['gene_symbol']) ?></td>
                <td><?= e($m['locus'] ?? '') ?></td>
                <td><?= e(trim(($m['reference_allele'] ?? '') . ' / ' . ($m['alternate_allele'] ?? ''), ' /')) ?></td>
                <td><?= e($m['allowed_values'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
