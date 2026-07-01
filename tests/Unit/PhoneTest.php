<?php
declare(strict_types=1);

use App\Support\Phone;

test('formatCz replaces leading 00 with +', function () {
    assert_same('+420724706228', Phone::formatCz('00420724706228'));
    assert_same('+421905123456', Phone::formatCz('00421905123456'));
});

test('formatCz leaves other formats intact', function () {
    assert_same('+420724706228', Phone::formatCz('+420724706228'));
    assert_same('724706228', Phone::formatCz('724706228'));
    assert_same('', Phone::formatCz(''));
    assert_same('', Phone::formatCz(null));
});
