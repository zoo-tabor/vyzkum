<?php
declare(strict_types=1);

use App\Support\FileStorage;

test('sanitize lowercases, slugifies and strips traversal', function () {
    assert_same('border-collie', FileStorage::sanitize('Border Collie!'));
    assert_same('etc', FileStorage::sanitize('../etc'));
    assert_same('x', FileStorage::sanitize(''));
    assert_same('a_b-c', FileStorage::sanitize('a_b-c'));
});

test('relativeDir sorts by breed / owner / dog', function () {
    assert_same('border-collie/owner_5/dog_12', FileStorage::relativeDir('Border Collie', 5, 12));
});

test('storedName is renamed with category and extension', function () {
    $name = FileStorage::storedName('health', 'pdf');
    assert_true((bool) preg_match('/^health_\d{8}_\d{6}_[0-9a-f]{8}\.pdf$/', $name), 'format: ' . $name);
});
