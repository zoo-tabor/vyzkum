<?php
declare(strict_types=1);

use App\Services\Totp;

// RFC 6238 reference secret (ASCII "12345678901234567890").
$secret = Totp::base32Encode('12345678901234567890');

test('base32 encode/decode roundtrips', function () {
    $raw = random_bytes(20);
    assert_same($raw, Totp::base32Decode(Totp::base32Encode($raw)));
});

test('codeAt matches RFC 6238 SHA1 vectors', function () use ($secret) {
    assert_same('287082', Totp::codeAt($secret, 59));
    assert_same('005924', Totp::codeAt($secret, 1234567890));
});

test('verify accepts current code and rejects a wrong one', function () use ($secret) {
    $code = Totp::codeAt($secret, 59);
    assert_true(Totp::verify($secret, $code, 1, 59));
    assert_true(Totp::verify($secret, $code, 1, 59 + 30), 'sousedni okno v toleranci');
    assert_false(Totp::verify($secret, '111111', 1, 59));
    assert_false(Totp::verify($secret, 'abc', 1, 59), 'nevalidni delka');
});
