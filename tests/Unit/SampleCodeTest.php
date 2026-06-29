<?php
declare(strict_types=1);

use App\Support\SampleCode;

test('sampleId has SMP-YEAR-HEX format and is unique', function () {
    $a = SampleCode::sampleId(2026);
    assert_true((bool) preg_match('/^SMP-2026-[0-9A-F]{8}$/', $a), 'format: ' . $a);
    assert_false(SampleCode::sampleId() === SampleCode::sampleId());
});

test('token is url-safe and non-empty', function () {
    $t = SampleCode::token();
    assert_true(strlen($t) >= 24);
    assert_true((bool) preg_match('/^[A-Za-z0-9_-]+$/', $t), 'token: ' . $t);
});
