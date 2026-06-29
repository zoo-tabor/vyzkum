<?php
declare(strict_types=1);

use App\Services\TokenService;

test('generate returns 64 hex chars and is unique', function () {
    $a = TokenService::generate();
    $b = TokenService::generate();
    assert_same(64, strlen($a));
    assert_true(ctype_xdigit($a));
    assert_false($a === $b, 'tokeny musi byt unikatni');
});

test('hash is deterministic sha256 hex and differs from raw token', function () {
    $token = 'abc123';
    assert_same(hash('sha256', $token), TokenService::hash($token));
    assert_same(64, strlen(TokenService::hash($token)));
    assert_false(TokenService::hash($token) === $token);
});
