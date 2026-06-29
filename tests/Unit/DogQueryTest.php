<?php
declare(strict_types=1);

use App\Support\DogQuery;

test('orderBy uses whitelist and safe direction', function () {
    assert_same('d.name ASC, d.id ASC', DogQuery::orderBy('name', 'asc'));
    assert_same('b.name DESC, d.id DESC', DogQuery::orderBy('breed', 'desc'));
    // unknown sort key falls back to name; junk direction -> ASC
    assert_same('d.name ASC, d.id ASC', DogQuery::orderBy('drop table', 'sideways'));
});

test('no filters yields 1=1 and no params', function () {
    $r = DogQuery::filters([], null);
    assert_same('1=1', $r['where']);
    assert_same([], $r['params']);
});

test('breed + name + status build bound params', function () {
    $r = DogQuery::filters(['q' => 'rex', 'status' => 'alive'], 5);
    assert_true(str_contains($r['where'], 'd.breed_id = :breed'));
    assert_true(str_contains($r['where'], 'd.name LIKE :q'));
    assert_true(str_contains($r['where'], 'd.death_date IS NULL'));
    assert_same(5, $r['params']['breed']);
    assert_same('%rex%', $r['params']['q']);
});

test('code filter matches chip or pedigree and LIKE wildcards are escaped', function () {
    $r = DogQuery::filters(['code' => '100%_'], null);
    assert_true(str_contains($r['where'], 'd.chip_number LIKE :code OR d.pedigree_number LIKE :code'));
    assert_same('%100\%\_%', $r['params']['code']);
});

test('dead status filter', function () {
    $r = DogQuery::filters(['status' => 'dead'], null);
    assert_true(str_contains($r['where'], 'd.death_date IS NOT NULL'));
});
