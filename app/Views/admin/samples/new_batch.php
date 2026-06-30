<?php
/** @var array<int, array<string, mixed>> $breeds */
/** @var array<int, array<string, mixed>> $vets */
/** @var int|null $currentBreedId */
/** @var string|null $error */
?>
<div class="page-head"><h1>Nová dávka vzorků</h1></div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p class="muted">Vygeneruje sample_id, tokenizované QR odkazy (veterinář + majitel) a tiskové štítky.</p>
    <form method="post" action="/admin/samples/new-batch">
        <?= \App\Core\Csrf::field() ?>
        <div class="form-row">
            <div>
                <label for="count">Počet sad *</label>
                <input type="number" id="count" name="count" min="1" max="200" value="20" required>
            </div>
            <div>
                <label for="breed_id">Plemeno</label>
                <select id="breed_id" name="breed_id">
                    <option value="">- nevybráno -</option>
                    <?php foreach ($breeds as $b): ?>
                        <option value="<?= (int) $b['id'] ?>"<?= $currentBreedId === (int) $b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="vet_id">Veterinář / klinika</label>
                <select id="vet_id" name="vet_id">
                    <option value="">Bez přiřazení</option>
                    <?php foreach ($vets as $v): ?>
                        <option value="<?= (int) $v['id'] ?>"><?= e(trim($v['name'] . (($v['clinic_name'] ?? '') ? ' / ' . $v['clinic_name'] : ''))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <label for="label">Popis dávky</label>
        <input type="text" id="label" name="label" placeholder="napr. duben 2026 / klinika Praha">

        <p class="muted">Maximum jedné dávky je 200 sad. QR odkazy se uloží k dávce kvůli pozdějšímu tisku.</p>
        <button type="submit" class="btn btn--primary">Vygenerovat dávku</button>
        <a class="btn" href="/admin/samples">Zrušit</a>
    </form>
</div>
