<?php
/** @var array<int, array<string, mixed>> $vets */
/** @var string|null $error */
?>
<div class="page-head"><h1><?= t('Nová dávka vzorků') ?></h1></div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p class="muted"><?= t('Vygeneruje sample_id, tokenizované QR odkazy (veterinář + majitel) a tiskové štítky. Plemeno se nezadává - vyplní ho majitel při registraci přes QR.') ?></p>
    <form method="post" action="/admin/samples/new-batch">
        <?= \App\Core\Csrf::field() ?>
        <div class="form-row">
            <div>
                <label for="count"><?= t('Počet sad') ?> *</label>
                <input type="number" id="count" name="count" min="1" max="200" value="20" required>
            </div>
            <div>
                <label for="vet_id"><?= t('Veterinář / klinika') ?></label>
                <select id="vet_id" name="vet_id">
                    <option value=""><?= t('Bez přiřazení') ?></option>
                    <?php foreach ($vets as $v): ?>
                        <option value="<?= (int) $v['id'] ?>"><?= e(trim($v['name'] . (($v['clinic_name'] ?? '') ? ' / ' . $v['clinic_name'] : ''))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="label"><?= t('Popis dávky') ?></label>
                <input type="text" id="label" name="label" placeholder="<?= e(t('např. duben 2026 / klinika Praha')) ?>">
            </div>
        </div>

        <p class="muted"><?= t('Maximum jedné dávky je 200 sad. QR odkazy se uloží k dávce kvůli pozdějšímu tisku.') ?></p>
        <button type="submit" class="btn btn--primary"><?= t('Vygenerovat dávku') ?></button>
        <a class="btn" href="/admin/samples"><?= t('Zrušit') ?></a>
    </form>
</div>
