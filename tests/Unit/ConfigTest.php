<?php
declare(strict_types=1);

use App\Core\Config;

test('config parses values, casts booleans and falls back to defaults', function () {
    $tmp = (string) tempnam(sys_get_temp_dir(), 'env');
    file_put_contents(
        $tmp,
        "APP_DEBUG=true\n" .
        "MAIL_ENABLED=false\n" .
        "FOO=bar\n" .
        "# komentar se ignoruje\n" .
        "EMPTY=\n" .
        "QUOTED=\"hello world\"\n"
    );

    $config = Config::load($tmp);

    assert_same('bar', $config->get('FOO'));
    assert_true($config->get('APP_DEBUG'));
    assert_false($config->get('MAIL_ENABLED'));
    assert_same('hello world', $config->get('QUOTED'));
    assert_same('def', $config->get('EMPTY', 'def'), 'prazdna hodnota vraci default');
    assert_same('fallback', $config->get('MISSING', 'fallback'));

    unlink($tmp);
});
