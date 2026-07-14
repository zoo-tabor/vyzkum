<?php
declare(strict_types=1);

use App\Support\I18n;

test('available locales come from registry and isValid checks them', function () {
    I18n::flush();
    $avail = I18n::available();
    assert_true(isset($avail['cs']) && isset($avail['en']) && isset($avail['es']), 'cs/en/es v registru');
    assert_true(I18n::isValid('de'), 'de je platny');
    assert_false(I18n::isValid('xx'), 'xx neni platny');
});

test('name and flag come from the registry', function () {
    I18n::flush();
    assert_same('Čeština', I18n::name('cs'));
    assert_same('cz', I18n::flag('cs'));
    assert_same('English', I18n::name('en'));
    assert_same('gb', I18n::flag('en'));
    assert_same('Русский', I18n::name('ru'));
});

test('default locale returns source text and interpolates params', function () {
    I18n::flush();
    assert_same('cs', I18n::locale());
    assert_same('Přihlásit se', I18n::t('Přihlásit se'));
    assert_same('Vítej, Honzo', I18n::t('Vítej, {name}', ['name' => 'Honzo']));
});

test('missing translation falls back to Czech source', function () {
    I18n::flush();
    I18n::setLocale('en'); // klic mimo katalog -> fallback na cesky zdroj
    assert_same('en', I18n::locale());
    // synteticky klic, ktery v zadnem katalogu neni (katalogy uz jsou plnene preklady)
    assert_same('Neexistující zdrojový text', I18n::t('Neexistující zdrojový text'));
    I18n::flush();
});

test('invalid locale falls back to default', function () {
    I18n::flush();
    I18n::setLocale('xx');
    assert_same('cs', I18n::locale());
});

test('tc falls back to plain source when no context translation', function () {
    I18n::flush();
    assert_same('Stav', I18n::tc('sample', 'Stav'));
    assert_same('Stav', I18n::tc('dog', 'Stav'));
    I18n::flush();
});

test('td returns Czech fallback for source locale and for missing keys', function () {
    I18n::flush();
    // cs = zdroj -> vzdy fallback (kanonicky cesky label)
    assert_same('Nemoc', I18n::td('death_causes', '1', 'Nemoc'));
    I18n::setLocale('en'); // katalog kodu je zatim prazdny -> fallback na cesky zdroj
    assert_same('Nemoc', I18n::td('death_causes', '1', 'Nemoc'));
    assert_same('Neznámé', I18n::td('death_causes', 'neexistuje', 'Neznámé'));
    I18n::flush();
});
