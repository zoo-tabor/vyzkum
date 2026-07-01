<?php
declare(strict_types=1);

use App\Support\Countries;

test('isValid accepts known alpha-3 codes case-insensitively', function () {
    assert_true(Countries::isValid('CZE'));
    assert_true(Countries::isValid('cze'));
    assert_false(Countries::isValid('XXX'));
});

test('name resolves code to Czech name, falls back to code', function () {
    assert_same('Česko', Countries::name('CZE'));
    assert_same('Spojené království', Countries::name('GBR'));
    assert_same(null, Countries::name(''));
    assert_same('ZZZ', Countries::name('ZZZ'));
});

test('all() is sorted by name and non-empty', function () {
    $all = Countries::all();
    assert_true(count($all) > 150);
    $values = array_values($all);
    $sorted = $values;
    asort($sorted, SORT_LOCALE_STRING);
    assert_same(array_values($sorted), $values);
});
