<?php
declare(strict_types=1);

use App\Support\Paginator;

test('empty result set has one page and zero range', function () {
    $p = new Paginator(0, 1, 25);
    assert_same(1, $p->totalPages);
    assert_same(1, $p->page);
    assert_same(0, $p->offset);
    assert_same(0, $p->from());
    assert_same(0, $p->to());
    assert_false($p->hasPrev());
    assert_false($p->hasNext());
});

test('pagination math and clamping', function () {
    $p = new Paginator(50, 2, 25);
    assert_same(2, $p->totalPages);
    assert_same(2, $p->page);
    assert_same(25, $p->offset);
    assert_same(26, $p->from());
    assert_same(50, $p->to());
    assert_true($p->hasPrev());
    assert_false($p->hasNext());

    // page above range is clamped to last page
    $clamped = new Paginator(50, 99, 25);
    assert_same(2, $clamped->page);
    // page below 1 is clamped to first page
    $low = new Paginator(50, 0, 25);
    assert_same(1, $low->page);
});

test('last partial page reports correct range', function () {
    $p = new Paginator(53, 3, 25);
    assert_same(3, $p->totalPages);
    assert_same(51, $p->from());
    assert_same(53, $p->to());
});
