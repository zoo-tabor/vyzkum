<?php
declare(strict_types=1);

use App\Support\Dates;

test('fromCz parses valid DD.MM.RRRR', function () {
    assert_same('2020-12-25', Dates::fromCz('25.12.2020'));
    assert_same('2021-03-05', Dates::fromCz('5.3.2021'));
});

test('fromCz rejects invalid input', function () {
    assert_same(null, Dates::fromCz('32.01.2020'));
    assert_same(null, Dates::fromCz('2020-12-25'));
    assert_same(null, Dates::fromCz('abc'));
    assert_same(null, Dates::fromCz(''));
});

test('toCz formats and tolerates empty', function () {
    assert_same('25.12.2020', Dates::toCz('2020-12-25'));
    assert_same('', Dates::toCz(null));
    assert_same('', Dates::toCz(''));
});

test('toCzDateTime formats datetime and tolerates empty', function () {
    assert_same('25.12.2020, 14:30:05', Dates::toCzDateTime('2020-12-25 14:30:05'));
    assert_same('', Dates::toCzDateTime(null));
    assert_same('', Dates::toCzDateTime(''));
    // fallback na samotne datum
    assert_same('25.12.2020', Dates::toCzDateTime('2020-12-25'));
});
