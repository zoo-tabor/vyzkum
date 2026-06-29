<?php
declare(strict_types=1);

use App\Services\Auth;

test('password hashing roundtrips and rejects wrong password', function () {
    $hash = Auth::hash('a-strong-password');
    assert_true(password_verify('a-strong-password', $hash));
    assert_false(password_verify('wrong', $hash));
});

test('preferred algo is Argon2id when available', function () {
    $expected = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    assert_same($expected, Auth::algo());
});
