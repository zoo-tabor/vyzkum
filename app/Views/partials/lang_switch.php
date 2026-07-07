<?php
// Prepinac jazyka. Vykresli odkazy na /locale/{kod}?r=<aktualni cesta> s nazvy v
// originalnim jazyce. Server-side, bez JS. Zobrazi se jen kdyz je vic nez 1 jazyk.
use App\Support\I18n;

$__langCur = I18n::locale();
$__langAvail = I18n::available();
if (count($__langAvail) < 2) {
    return;
}
$__langPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
?>
<nav class="lang-switch" aria-label="Jazyk">
    <?php foreach ($__langAvail as $__langCode => $__langName): ?>
        <?php if ($__langCode === $__langCur): ?>
            <span class="lang-switch__item is-active" aria-current="true"><?= e($__langName) ?></span>
        <?php else: ?>
            <a class="lang-switch__item" href="/locale/<?= e($__langCode) ?>?r=<?= e(rawurlencode($__langPath)) ?>"><?= e($__langName) ?></a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
