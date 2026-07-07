<?php
/** @var array<int, array<string, mixed>> $breeds */
/** @var int|null $currentBreedId */
/** @var string|null $error */
?>
<div class="page-head"><h1><?= t('Ruční vzorek') ?></h1></div>

<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p class="muted"><?= t('Vzorek, který dorazil napřímo výzkumnému týmu (bez odběru veterinářem a bez dávky/QR).') ?></p>
    <form method="post" action="/admin/samples/manual">
        <?= \App\Core\Csrf::field() ?>
        <div class="form-row">
            <div>
                <label for="sample_id"><?= t('Číslo vzorku') ?> *</label>
                <input type="text" id="sample_id" name="sample_id" placeholder="<?= e(t('např. CKCML1')) ?>" required>
            </div>
            <div>
                <label for="breed_id"><?= t('Plemeno (nepovinné)') ?></label>
                <select id="breed_id" name="breed_id">
                    <option value=""><?= t('- nevybráno -') ?></option>
                    <?php foreach ($breeds as $b): ?>
                        <option value="<?= (int) $b['id'] ?>"<?= $currentBreedId === (int) $b['id'] ? ' selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="received_at"><?= t('Datum přijetí') ?></label>
                <input type="date" id="received_at" name="received_at">
            </div>
        </div>

        <label for="samp-dog-search"><?= t('Pes (nepovinné)') ?></label>
        <div class="ac" id="samp-ac-wrap">
            <input type="text" id="samp-dog-search" autocomplete="off" placeholder="<?= e(t('Napište jméno psa a vyberte z nabídky')) ?>">
            <input type="hidden" id="samp-dog-id" name="dog_id" value="">
            <div class="ac-list" id="samp-ac" hidden></div>
        </div>

        <p class="muted"><?= t('Vzorek se založí se stavem {status}. Když vyberete psa, rovnou se k němu přiřadí; jinak zůstane nepřiřazený a napojí se později (např. při importu genetiky podle čísla vzorku).', ['status' => '<code>sample_received</code>']) ?></p>
        <button type="submit" class="btn btn--primary"><?= t('Přidat vzorek') ?></button>
        <a class="btn" href="/admin/samples"><?= t('Zrušit') ?></a>
    </form>
</div>

<script>
(function () {
    var input = document.getElementById('samp-dog-search');
    var hidden = document.getElementById('samp-dog-id');
    var list = document.getElementById('samp-ac');
    if (!input || !hidden || !list) { return; }
    var timer;

    function close() { list.hidden = true; list.innerHTML = ''; }

    input.addEventListener('input', function () {
        hidden.value = ''; // vyber je platny jen po kliknuti z nabidky
        clearTimeout(timer);
        var q = input.value.trim();
        if (q.length < 2) { close(); return; }
        timer = setTimeout(function () {
            fetch('/admin/samples/dog-suggest?q=' + encodeURIComponent(q))
                .then(function (r) { return r.json(); })
                .then(function (items) {
                    list.innerHTML = '';
                    if (!items.length) { close(); return; }
                    items.forEach(function (it) {
                        var d = document.createElement('button');
                        d.type = 'button';
                        d.className = 'ac-item';
                        d.innerHTML = '<strong></strong> <span class="muted"></span>';
                        d.querySelector('strong').textContent = it.name;
                        d.querySelector('.muted').textContent = it.breed_name || '';
                        d.addEventListener('click', function () {
                            input.value = it.name;
                            hidden.value = it.id;
                            close();
                        });
                        list.appendChild(d);
                    });
                    list.hidden = false;
                }).catch(close);
        }, 200);
    });

    document.addEventListener('click', function (e) {
        if (!document.getElementById('samp-ac-wrap').contains(e.target)) { close(); }
    });
})();
</script>
