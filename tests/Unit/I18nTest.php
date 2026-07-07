<?php
declare(strict_types=1);

use App\Support\I18n;

test('available locales come from registry and isValid checks them', function () {
    I18n::flush();
    $avail = I18n::available();
    assert_true(isset($avail['cs']) && isset($avail['en']) && isset($avail['es']), 'cs/en/es v registru');
    assert_true(I18n::isValid('en'), 'en je platny');
    assert_false(I18n::isValid('de'), 'de neni platny');
});

test('default locale returns source text and interpolates params', function () {
    I18n::flush();
    assert_same('cs', I18n::locale());
    assert_same('Přihlásit se', I18n::t('Přihlásit se'));
    assert_same('Vítej, Honzo', I18n::t('Vítej, {name}', ['name' => 'Honzo']));
});

test('missing translation falls back to Czech source', function () {
    I18n::flush();
    I18n::setLocale('en'); // katalog en.php je zatim prazdny -> fallback na zdroj
    assert_same('en', I18n::locale());
    assert_same('Uložit', I18n::t('Uložit'));
    I18n::flush();
});

test('invalid locale falls back to default', function () {
    I18n::flush();
    I18n::setLocale('de');
    assert_same('cs', I18n::locale());
});

test('tc falls back to plain source when no context translation', function () {
    I18n::flush();
    assert_same('Stav', I18n::tc('sample', 'Stav'));
    assert_same('Stav', I18n::tc('dog', 'Stav'));
    I18n::flush();
});
