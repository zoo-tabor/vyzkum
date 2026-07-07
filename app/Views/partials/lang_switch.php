<?php
// Prepinac jazyka: tlacitko (vzhledem jako "Odhlasit") -> modalni okno s jazyky v
// mrizce (vlajka + nazev). Prepnuti je server-side (odkaz /locale/{kod}); modal jen
// otevira/zavira JS (native <dialog>).
use App\Support\I18n;

$__langCur = I18n::locale();
$__langAvail = I18n::available();
if (count($__langAvail) < 2) {
    return;
}
$__langPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
?>
<div class="lang-switch">
    <button type="button" class="btn btn--ghost lang-btn" data-lang-open aria-haspopup="dialog">
        <img class="lang-flag" src="<?= e(asset('assets/flags/' . I18n::flag($__langCur) . '.svg')) ?>" alt="" width="22" height="16">
        <span><?= e(I18n::name($__langCur)) ?></span>
    </button>

    <dialog class="lang-modal" data-lang-modal aria-label="Jazyk / Language">
        <div class="lang-modal__head">
            <strong>Jazyk / Language</strong>
            <button type="button" class="lang-modal__close" data-lang-close aria-label="Zavřít">&times;</button>
        </div>
        <div class="lang-grid">
            <?php foreach ($__langAvail as $__langCode => $__langMeta): ?>
                <a class="lang-cell<?= $__langCode === $__langCur ? ' is-active' : '' ?>"
                   href="/locale/<?= e($__langCode) ?>?r=<?= e(rawurlencode($__langPath)) ?>">
                    <img class="lang-flag" src="<?= e(asset('assets/flags/' . $__langMeta['flag'] . '.svg')) ?>" alt="" width="32" height="24">
                    <span><?= e($__langMeta['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </dialog>
</div>
<script>
(function () {
    var btn = document.querySelector('[data-lang-open]');
    var modal = document.querySelector('[data-lang-modal]');
    if (!btn || !modal) { return; }
    btn.addEventListener('click', function () {
        if (typeof modal.showModal === 'function') { modal.showModal(); }
        else { modal.setAttribute('open', ''); }
    });
    var close = modal.querySelector('[data-lang-close]');
    if (close) { close.addEventListener('click', function () { modal.close(); }); }
    // Klik na podklad (mimo obsah dialogu) zavre.
    modal.addEventListener('click', function (e) {
        if (e.target === modal) { modal.close(); }
    });
})();
</script>
