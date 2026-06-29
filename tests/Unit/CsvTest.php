<?php
declare(strict_types=1);

use App\Support\Csv;

test('detectDelimiter picks the dominant separator', function () {
    assert_same(',', Csv::detectDelimiter('a,b,c'));
    assert_same(';', Csv::detectDelimiter('a;b;c'));
    assert_same("\t", Csv::detectDelimiter("a\tb\tc"));
    assert_same(',', Csv::detectDelimiter('single'));
});

test('parse maps rows to header keys (comma)', function () {
    $r = Csv::parse("breed_slug,dog_name\nborder-collie,Rex\n");
    assert_same(['breed_slug', 'dog_name'], $r['header']);
    assert_same(1, count($r['rows']));
    assert_same('border-collie', $r['rows'][0]['breed_slug']);
    assert_same('Rex', $r['rows'][0]['dog_name']);
});

test('parse handles BOM, quoted commas and semicolon delimiter', function () {
    $r = Csv::parse("\xEF\xBB\xBFa;b\n\"x;y\";z\n");
    // semicolon dominates -> but quoted field keeps the ; inside
    assert_same(['a', 'b'], $r['header']);
    assert_same('x;y', $r['rows'][0]['a']);
    assert_same('z', $r['rows'][0]['b']);
});

test('parse tolerates missing trailing cells and blank lines', function () {
    $r = Csv::parse("a,b,c\n1,2\n\n3,4,5\n");
    assert_same(2, count($r['rows']));
    assert_same('', $r['rows'][0]['c']);
    assert_same('5', $r['rows'][1]['c']);
});

test('empty content yields no rows', function () {
    $r = Csv::parse("   ");
    assert_same([], $r['header']);
    assert_same([], $r['rows']);
});
