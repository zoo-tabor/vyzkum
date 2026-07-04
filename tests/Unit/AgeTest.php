<?php
declare(strict_types=1);

use App\Support\Age;

test('years counts whole years to reference date', function () {
    assert_same(5, Age::years('2015-06-01', '2020-06-01'));
    assert_same(4, Age::years('2015-06-01', '2020-05-31'));
    assert_same(0, Age::years('2020-01-01', '2020-12-31'));
});

test('years returns null for missing birth or reference before birth', function () {
    assert_same(null, Age::years(null, '2020-01-01'));
    assert_same(null, Age::years('', '2020-01-01'));
    assert_same(null, Age::years('2020-01-01', '2019-01-01'));
});

test('yearsFloat returns age with two decimals from day count', function () {
    assert_same(5.0, Age::yearsFloat('2015-06-01', '2020-05-31')); // 1826 dnu / 365.25 = 5.0
    assert_same(0.5, Age::yearsFloat('2020-01-01', '2020-07-02')); // 183 dnu / 365.25 ~ 0.50
    assert_same(null, Age::yearsFloat(null, '2020-01-01'));
    assert_same(null, Age::yearsFloat('2020-01-01', '2019-01-01'));
});

test('referenceDate follows priority death -> alive -> sample', function () {
    assert_same('2022-03-04', Age::referenceDate('2022-03-04', '2023-01-01', '2021-01-01'));
    assert_same('2023-01-01', Age::referenceDate(null, '2023-01-01', '2021-01-01'));
    assert_same('2021-01-01', Age::referenceDate(null, null, '2021-01-01 10:00:00'));
    assert_same(null, Age::referenceDate(null, null, null));
});
