<?php
declare(strict_types=1);

use App\Support\SecurityHeaders;

test('security headers contain the key protections', function () {
    $h = SecurityHeaders::all();
    assert_same('nosniff', $h['X-Content-Type-Options']);
    assert_same('DENY', $h['X-Frame-Options']);
    assert_true(isset($h['Content-Security-Policy']));
    assert_true(str_contains($h['Content-Security-Policy'], "default-src 'self'"));
    assert_true(str_contains($h['Content-Security-Policy'], "frame-ancestors 'none'"));
});
