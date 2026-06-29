<?php
declare(strict_types=1);

use App\Services\BreedContext;

test('breed context stores and clears selection', function () {
    $_SESSION = [];
    assert_true(BreedContext::isAll());

    BreedContext::set(7);
    assert_same(7, BreedContext::current());
    assert_false(BreedContext::isAll());

    BreedContext::set(null);
    assert_same(null, BreedContext::current());
    assert_true(BreedContext::isAll());
});

test('recent breeds are deduped, most-recent-first and capped', function () {
    $_SESSION = [];
    foreach ([1, 2, 3, 1, 4, 5, 6] as $id) {
        BreedContext::set($id);
    }
    // 1 was re-selected so it should be deduped; max 5 entries kept.
    assert_same([6, 5, 4, 1, 3], BreedContext::recent());
});
