<?php
declare(strict_types=1);

use App\Support\Gwas;

test('label maps known GWAS codes and falls back to dash', function () {
    assert_same('Odesláno', Gwas::label('GWAS_sent'));
    assert_same('Nevyšlo', Gwas::label('GWAS_failed'));
    assert_same('Vyšlo', Gwas::label('GWAS_ok'));
    assert_same('Ne', Gwas::label('GWAS_none'));
    assert_same('-', Gwas::label(null));
    assert_same('-', Gwas::label(''));
    assert_same('-', Gwas::label('nonsense'));
});

test('options include empty choice and all statuses', function () {
    $opts = Gwas::options();
    assert_same('- neuvedeno -', $opts['']);
    assert_true(isset($opts['GWAS_none']));
    assert_same(5, count($opts));
});
