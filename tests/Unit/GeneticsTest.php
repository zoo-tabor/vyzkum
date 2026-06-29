<?php
declare(strict_types=1);

use App\Support\Genetics;

test('markerColumns extracts _genotype columns and codes', function () {
    $cols = Genetics::markerColumns(['sample_id', 'expected_phenotype', 'B3GALNT1_genotype', 'NLRP1_genotype', 'lab_name']);
    assert_same(2, count($cols));
    assert_same('B3GALNT1_genotype', $cols[0]['column']);
    assert_same('B3GALNT1', $cols[0]['code']);
    assert_same('NLRP1', $cols[1]['code']);
});

test('isEmptyValue treats blank/X/N-A as no result', function () {
    assert_true(Genetics::isEmptyValue(''));
    assert_true(Genetics::isEmptyValue('X'));
    assert_true(Genetics::isEmptyValue(' x '));
    assert_true(Genetics::isEmptyValue('N/A'));
    assert_false(Genetics::isEmptyValue('GG'));
});

test('splitGenotype splits two-letter genotypes, null for empty', function () {
    assert_same(['allele_1' => 'G', 'allele_2' => 'G', 'genotype' => 'GG'], Genetics::splitGenotype('gg'));
    assert_same(['allele_1' => 'G', 'allele_2' => 'C', 'genotype' => 'GC'], Genetics::splitGenotype('GC'));
    assert_same(null, Genetics::splitGenotype('X'));
    $other = Genetics::splitGenotype('del/del');
    assert_same(null, $other['allele_1']);
    assert_same('DEL/DEL', $other['genotype']);
});
