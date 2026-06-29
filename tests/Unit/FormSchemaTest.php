<?php
declare(strict_types=1);

use App\Support\FormSchema;

test('type whitelist + needsOptions', function () {
    assert_true(FormSchema::isValidType('single_choice'));
    assert_false(FormSchema::isValidType('drop_table'));
    assert_true(FormSchema::needsOptions('multiple_choice'));
    assert_false(FormSchema::needsOptions('short_text'));
});

test('slug strips diacritics and non-alnum', function () {
    assert_same('ano_zije', FormSchema::slug('Ano zije'));
    assert_same('prilis', FormSchema::slug('Příliš'));
    assert_same('q', FormSchema::slug('!!!'));
});

test('parseOptions: plain labels get slug keys', function () {
    $r = FormSchema::parseOptions("Ano\nNe");
    assert_same([['key' => 'ano', 'label' => 'Ano'], ['key' => 'ne', 'label' => 'Ne']], $r);
});

test('parseOptions: explicit key|label and dedup', function () {
    $r = FormSchema::parseOptions("yes|Ano zije\nA\nA");
    assert_same('yes', $r[0]['key']);
    assert_same('Ano zije', $r[0]['label']);
    assert_same('a', $r[1]['key']);
    assert_same('a_2', $r[2]['key']);
});
