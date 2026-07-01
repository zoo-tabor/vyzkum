<?php
/** @var array<int, array<string, mixed>> $breeds */
/** @var int|null $currentBreedId */
/** @var string|null $error */
?>
<div class="page-head"><h1>Ruční vzorek</h1></div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p class="muted">Vzorek, který dorazil napřímo výzkumnému týmu (bez odběru veterinářem a bez dávky/QR).</p>
    <form method="post" action="/admin/samples/manual">
        <?= \App\Core\Csrf::field() ?>
        <div class="form-row">
            <div>
                <label for="sample_id">Číslo vzorku *</label>
                <input type="text" id="sample_id" name="sample_id" placeholder="např. CKCML1" required>
            </div>
            <div>
                <label for="breed_id">Plemeno (nepovinné)</label>
                <select id="breed_id" name="breed_id">
                    <option value="">- nevybráno -</option>
                    <?php foreach ($breeds as $b): ?>
                        <option value="<?= (int) $b['id'] ?>"<?= $currentBreedId === (int) $b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="received_at">Datum přijetí</label>
                <input type="date" id="received_at" name="received_at">
            </div>
        </div>
        <p class="muted">Vzorek se založí se stavem <code>sample_received</code>. Napojení na psa proběhne později (např. při zadání psa nebo importu genetiky podle stejného čísla vzorku).</p>
        <button type="submit" class="btn btn--primary">Přidat vzorek</button>
        <a class="btn" href="/admin/samples">Zrušit</a>
    </form>
</div>
