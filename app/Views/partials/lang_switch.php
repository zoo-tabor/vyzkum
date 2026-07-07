<?php
// Prepinac jazyka: rozbalovaci (native <details>, bez JS pro prepnuti). Souhrn
// ukazuje vlajku (vlevo) + nazev aktualniho jazyka (vpravo) v zakulacenem boxu;
// po rozbaleni nabidne vsechny jazyky (vlajka + nazev). Nazvy v originalnim jazyce.
use App\Support\I18n;

$__langCur = I18n::locale();
$__langAvail = I18n::available();
if (count($__langAvail) < 2) {
    return;
}
$__langPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
?>
<details class="lang-switch">
    <summary class="lang-switch__current">
        <img class="lang-switch__flag" src="<?= e(asset('assets/flags/' . I18n::flag($__langCur) . '.svg')) ?>" alt="" width="20" height="15">
        <span class="lang-switch__name"><?= e(I18n::name($__langCur)) ?></span>
        <span class="lang-switch__caret" aria-hidden="true">&#9662;</span>
    </summary>
    <div class="lang-switch__menu">
        <?php foreach ($__langAvail as $__langCode => $__langMeta): ?>
            <a class="lang-switch__opt<?= $__langCode === $__langCur ? ' is-active' : '' ?>"
               href="/locale/<?= e($__langCode) ?>?r=<?= e(rawurlencode($__langPath)) ?>">
                <img class="lang-switch__flag" src="<?= e(asset('assets/flags/' . $__langMeta['flag'] . '.svg')) ?>" alt="" width="20" height="15">
                <span><?= e($__langMeta['name']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</details>
<script>
(function () {
    var box = document.querySelector('details.lang-switch');
    if (!box) { return; }
    document.addEventListener('click', function (e) {
        if (!box.contains(e.target)) { box.removeAttribute('open'); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { box.removeAttribute('open'); }
    });
})();
</script>
