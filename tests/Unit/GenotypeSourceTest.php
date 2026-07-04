<?php
declare(strict_types=1);

use App\Support\GenotypeSource;

test('normalize accepts known sources and rejects others', function () {
    assert_same('sekvenace', GenotypeSource::normalize('sekvenace'));
    assert_same('GWAS', GenotypeSource::normalize('GWAS'));
    assert_same(null, GenotypeSource::normalize(''));
    assert_same(null, GenotypeSource::normalize('nonsense'));
});

test('label and labelList map values and handle empty', function () {
    assert_same('Sekvenace', GenotypeSource::label('sekvenace'));
    assert_same('-', GenotypeSource::label(null));
    assert_same('-', GenotypeSource::labelList(''));
    assert_same('Sekvenace', GenotypeSource::labelList('sekvenace'));
    assert_same('GWAS, Sekvenace', GenotypeSource::labelList('GWAS,sekvenace'));
});

test('default is sekvenace', function () {
    assert_same('sekvenace', GenotypeSource::DEFAULT);
});
