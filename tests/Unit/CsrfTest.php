<?php
declare(strict_types=1);

use App\Core\Csrf;

test('csrf token is stable within a session', function () {
    $_SESSION = [];
    $a = Csrf::token();
    $b = Csrf::token();
    assert_same($a, $b, 'token se nesmi menit pri opakovanem cteni');
    assert_true(strlen($a) >= 32);
});

test('csrf isValid matches only the stored token', function () {
    $_SESSION = ['_csrf' => 'expected-token'];
    assert_true(Csrf::isValid('expected-token'));
    assert_false(Csrf::isValid('wrong'));
    assert_false(Csrf::isValid(null));
    assert_false(Csrf::isValid(['array']));
});
