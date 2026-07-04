<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Repositories\DogRepository;
use App\Repositories\FormAssignmentRepository;

/**
 * Rozesle publikovany dotaznik majitelum psu daneho plemene: jeden ukol
 * (form_assignment) + jeden e-mail na psa. Text e-mailu (predmet + telo) edituje
 * admin pred odeslanim; v tele se nahrazuji zastupne znacky {pes}, {majitel}, {odkaz}.
 */
final class FormBroadcastService
{
    public function __construct(
        private DogRepository $dogs = new DogRepository(),
        private FormAssignmentRepository $assignments = new FormAssignmentRepository(),
    ) {
    }

    public const DEFAULT_SUBJECT = 'Dotazník k vašemu psovi - Výzkum ZOO Tábor';

    public static function defaultBody(string $formName): string
    {
        return "Dobrý den,\n\n"
            . "v rámci výzkumu dlouhověkosti psů ZOO Tábor vás prosíme o vyplnění dotazníku "
            . "\"{$formName}\" k vašemu psovi {pes}.\n\n"
            . "Dotazník vyplníte po přihlášení do portálu zde:\n{odkaz}\n\n"
            . "Předem děkujeme za spolupráci.\n\n"
            . "S pozdravem\nVýzkumný tým ZOO Tábor";
    }

    /**
     * @param array<string, mixed> $def     definice dotazniku (findDefinition)
     * @param array<string, mixed> $version publikovana verze
     * @return array{total:int, sent:int, failed:int, skipped:int}
     */
    public function send(array $def, array $version, string $subject, string $bodyTemplate, ?int $userId, bool $livingOnly = true): array
    {
        $defId = (int) $def['id'];
        $versionId = (int) $version['id'];
        $appUrl = rtrim((string) Config::instance()->get('APP_URL', ''), '/');

        $recipients = $this->dogs->recipientsForBreed((int) $def['breed_id'], $livingOnly);
        $result = ['total' => count($recipients), 'sent' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($recipients as $r) {
            $email = trim((string) ($r['email'] ?? ''));
            if ($email === '') {
                $this->assignments->create($defId, $versionId, (int) $r['dog_id'], (int) $r['owner_id'], null, 'skipped');
                $result['skipped']++;
                continue;
            }

            $link = $appUrl . '/portal/dogs/' . (int) $r['dog_id'] . '/forms/' . $defId;
            $body = $this->personalize($bodyTemplate, (string) $r['dog_name'], (string) $r['owner_name'], $link);

            $ok = MailService::send($email, $subject, $body, 'form_broadcast');
            $this->assignments->create($defId, $versionId, (int) $r['dog_id'], (int) $r['owner_id'], $email, $ok ? 'sent' : 'failed');
            $ok ? $result['sent']++ : $result['failed']++;
        }

        AuditService::log($userId, 'research_admin', 'form_broadcast', 'form_definition', (string) $defId, null, $result);
        return $result;
    }

    private function personalize(string $template, string $dogName, string $ownerName, string $link): string
    {
        $body = strtr($template, [
            '{pes}' => $dogName,
            '{majitel}' => $ownerName,
            '{odkaz}' => $link,
        ]);
        // Kdyz admin znacku {odkaz} z textu smazal, odkaz presto pripojime na konec.
        if (!str_contains($template, '{odkaz}')) {
            $body .= "\n\n" . $link;
        }
        return $body;
    }
}
