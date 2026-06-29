<?php
declare(strict_types=1);

use App\Support\FormConditions;

test('no condition -> always visible', function () {
    assert_true(FormConditions::isVisible(null, []));
    assert_true(FormConditions::isVisible(['foo' => 'bar'], []));
});

test('visible_if matches scalar answer', function () {
    $cfg = ['visible_if' => ['q' => 'je_pes_nazivu', 'eq' => 'no']];
    assert_true(FormConditions::isVisible($cfg, ['je_pes_nazivu' => 'no']));
    assert_false(FormConditions::isVisible($cfg, ['je_pes_nazivu' => 'yes']));
    assert_false(FormConditions::isVisible($cfg, []));
});

test('visible_if matches value inside multiple-choice array', function () {
    $cfg = ['visible_if' => ['q' => 'priznaky', 'eq' => 'kasel']];
    assert_true(FormConditions::isVisible($cfg, ['priznaky' => ['horecka', 'kasel']]));
    assert_false(FormConditions::isVisible($cfg, ['priznaky' => ['horecka']]));
});
