<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\EmailTemplateRepository;
use App\Repositories\TranslationRepository;
use App\Support\I18n;

/**
 * Sestavi a odesle transakcni e-mail ze sablony (email_templates) v jazyce prijemce.
 * Cesky zdroj je v DB (editovatelny z UI), preklady v tabulce translations. Kdyz
 * sablona/tabulka chybi, pouzije se vestaveny fallback (DEFAULTS) - e-mail se odesle
 * vzdy. Placeholdery ({odkaz}, {pes}, ...) se nahrazuji z $params.
 */
final class MailTemplateService
{
    /** @var array<string, array{subject:string, body:string}> zaloha pri chybejici sablone/tabulce */
    private const DEFAULTS = [
        'set_password' => [
            'subject' => 'Nastavení hesla - Výzkum ZOO Tábor',
            'body' => "Dobrý den,\n\ndo systému výzkumu plemen psů ZOO Tábor vám byl založen účet.\nPro nastavení hesla použijte tento odkaz (platí 1 měsíc):\n\n{odkaz}\n\nPo nastavení hesla se budete moci přihlásit a vidět své psy.\n\nS pozdravem\nVýzkumný tým ZOO Tábor",
        ],
        'password_reset' => [
            'subject' => 'Obnova hesla - Výzkum ZOO Tábor',
            'body' => "Dobrý den,\n\nobdrželi jsme žádost o obnovu hesla k vašemu účtu ve výzkumu plemen psů ZOO Tábor.\nNové heslo si nastavíte tímto odkazem (platí 2 hodiny):\n\n{odkaz}\n\nPokud jste o obnovu hesla nežádali, tento e-mail ignorujte - vaše heslo zůstává beze změny.\n\nS pozdravem\nVýzkumný tým ZOO Tábor",
        ],
        'ownership_transfer' => [
            'subject' => 'Převzetí psa - Výzkum ZOO Tábor',
            'body' => "Dobrý den,\n\nstávající majitel vás uvedl jako nového majitele psa v rámci výzkumu plemen psů ZOO Tábor.\nPro potvrzení převzetí psa použijte tento odkaz (platí 1 měsíc):\n\n{odkaz}\n\nPo potvrzení vám přijde odkaz pro nastavení hesla do portálu.\n\nS pozdravem\nVýzkumný tým ZOO Tábor",
        ],
        'form_broadcast' => [
            'subject' => 'Dotazník k vašemu psovi - Výzkum ZOO Tábor',
            'body' => "Dobrý den,\n\nv rámci výzkumu dlouhověkosti psů ZOO Tábor vás prosíme o vyplnění dotazníku \"{dotaznik}\" k vašemu psovi {pes}.\n\nDotazník vyplníte po přihlášení do portálu zde:\n{odkaz}\n\nPředem děkujeme za spolupráci.\n\nS pozdravem\nVýzkumný tým ZOO Tábor",
        ],
    ];

    /**
     * Sestavi predmet + telo sablony v danem jazyce (fallback cs) s dosazenymi placeholdery.
     *
     * @param array<string, string> $params placeholder (bez zavorek) => hodnota
     * @return array{subject:string, body:string}
     */
    public static function render(string $key, array $params = [], ?string $locale = null): array
    {
        $locale = $locale ?: I18n::locale();

        $tpl = (new EmailTemplateRepository())->find($key);
        $subject = $tpl !== null ? (string) $tpl['subject'] : (self::DEFAULTS[$key]['subject'] ?? '');
        $body = $tpl !== null ? (string) $tpl['body'] : (self::DEFAULTS[$key]['body'] ?? '');
        $templateId = $tpl !== null ? (int) $tpl['id'] : 0;

        // Preklad subject/body pro jazyk prijemce (jen kdyz mame DB sablonu s id).
        if ($locale !== I18n::defaultLocale() && $templateId > 0) {
            $tx = (new TranslationRepository())->allForFields(
                EmailTemplateRepository::ENTITY,
                ['subject', 'body'],
                [$templateId],
                $locale
            );
            $t = $tx[$templateId] ?? [];
            if (isset($t['subject']) && $t['subject'] !== '') {
                $subject = $t['subject'];
            }
            if (isset($t['body']) && $t['body'] !== '') {
                $body = $t['body'];
            }
        }

        // Dosazeni placeholderu.
        $replace = [];
        foreach ($params as $k => $v) {
            $replace['{' . $k . '}'] = (string) $v;
        }
        if ($replace !== []) {
            $subject = strtr($subject, $replace);
            $body = strtr($body, $replace);
        }

        return ['subject' => $subject, 'body' => $body];
    }

    /**
     * Sestavi a odesle e-mail ze sablony. Vraci uspech odeslani.
     *
     * @param array<string, string> $params
     */
    public static function send(string $key, string $to, array $params = [], ?string $locale = null): bool
    {
        $m = self::render($key, $params, $locale);
        return MailService::send($to, $m['subject'], $m['body'], $key);
    }
}
