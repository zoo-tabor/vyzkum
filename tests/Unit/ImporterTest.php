<?php
declare(strict_types=1);

use App\Services\DogOwnerImporter;

test('normalizeSex maps common variants', function () {
    assert_same('male', DogOwnerImporter::normalizeSex('M'));
    assert_same('male', DogOwnerImporter::normalizeSex('pes'));
    assert_same('female', DogOwnerImporter::normalizeSex('F'));
    assert_same('female', DogOwnerImporter::normalizeSex('fena'));
    assert_same('unknown', DogOwnerImporter::normalizeSex(''));
    assert_same('unknown', DogOwnerImporter::normalizeSex('neco'));
});

test('isDate accepts strict YYYY-MM-DD only', function () {
    assert_true(DogOwnerImporter::isDate('2017-02-01'));
    assert_false(DogOwnerImporter::isDate('2017-2-1'));
    assert_false(DogOwnerImporter::isDate('2017-13-01'));
    assert_false(DogOwnerImporter::isDate('01.02.2017'));
    assert_false(DogOwnerImporter::isDate(''));
});
