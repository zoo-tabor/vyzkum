<?php
/** @var array<int, array<string, mixed>> $breeds */
/** @var array<int, array<string, mixed>> $vets */
/** @var int|null $currentBreedId */
/** @var string|null $error */
?>
<div class="page-head"><h1>Nova davka vzorku</h1></div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p class="muted">Vygeneruje sample_id, tokenizovane QR odkazy (veterinar + majitel) a tiskove stitky.</p>
    <form method="post" action="/admin/samples/new-batch">
        <?= \App\Core\Csrf::field() ?>
        <div class="form-row">
            <div>
                <label for="count">Pocet sad *</label>
                <input type="number" id="count" name="count" min="1" max="200" value="20" required>
            </div>
            <div>
                <label for="breed_id">Plemeno</label>
                <select id="breed_id" name="breed_id">
                    <option value="">- nevybrano -</option>
                    <?php foreach ($breeds as $b): ?>
                        <option value="<?= (int) $b['id'] ?>"<?= $currentBreedId === (int) $b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="vet_id">Veterinar / klinika</label>
                <select id="vet_id" name="vet_id">
                    <option value="">Bez prirazeni</option>
                    <?php foreach ($vets as $v): ?>
                        <option value="<?= (int) $v['id'] ?>"><?= e(trim($v['name'] . (($v['clinic_name'] ?? '') ? ' / ' . $v['clinic_name'] : ''))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <label for="label">Popis davky</label>
        <input type="text" id="label" name="label" placeholder="napr. duben 2026 / klinika Praha">

        <p class="muted">Maximum jedne davky je 200 sad. QR odkazy se ulozi k davce kvuli pozdejsimu tisku.</p>
        <button type="submit" class="btn btn--primary">Vygenerovat davku</button>
        <a class="btn" href="/admin/samples">Zrusit</a>
    </form>
</div>
