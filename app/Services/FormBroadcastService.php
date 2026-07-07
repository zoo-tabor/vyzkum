<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Repositories\DogRepository;
use App\Repositories\FormAssignmentRepository;
use App\Repositories\TranslationRepository;
use App\Support\I18n;

/**
 * Rozesle publikovany dotaznik majitelum psu daneho plemene: jeden ukol
 * (form_assignment) + jeden e-mail na psa. Text e-mailu je sablona 'form_broadcast'
 * (editovatelna z admin UI vc. prekladu); kazdy majitel dostane e-mail ve svem
 * jazyce (owners.language, fallback cs). Placeholdery: {dotaznik}, {pes}, {majitel}, {odkaz}.
 */
final class FormBroadcastService
{
    public function __construct(
        private DogRepository $dogs = new DogRepository(),
        private FormAssignmentRepository $assignments = new FormAssignmentRepository(),
    ) {
    }

    /**
     * @param array<string, mixed> $def     definice dotazniku (findDefinition)
     * @param array<string, mixed> $version publikovana verze
     * @return array{total:int, sent:int, failed:int, skipped:int}
     */
    public function send(array $def, array $version, ?int $userId, bool $livingOnly = true): array
    {
        $defId = (int) $def['id'];
        $versionId = (int) $version['id'];
        $appUrl = rtrim((string) Config::instance()->get('APP_URL', ''), '/');
        $czName = (string) $def['name'];

        // Prelozene nazvy dotazniku (pro {dotaznik} v jazyce prijemce), fallback cs.
        $nameByLocale = (new TranslationRepository())->localesFor(TranslationRepository::FORM_DEFINITION, $defId, 'name');

        $recipients = $this->dogs->recipientsForBreed((int) $def['breed_id'], $livingOnly);
        $result = ['total' => count($recipients), 'sent' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($recipients as $r) {
            $email = trim((string) ($r['email'] ?? ''));
            if ($email === '') {
                $this->assignments->create($defId, $versionId, (int) $r['dog_id'], (int) $r['owner_id'], null, 'skipped');
                $result['skipped']++;
                continue;
            }

            $locale = (string) ($r['owner_language'] ?? '') ?: I18n::defaultLocale();
            $formName = ($locale !== I18n::defaultLocale() && !empty($nameByLocale[$locale])) ? $nameByLocale[$locale] : $czName;
            $link = $appUrl . '/portal/dogs/' . (int) $r['dog_id'] . '/forms/' . $defId;

            $ok = MailTemplateService::send('form_broadcast', $email, [
                'dotaznik' => $formName,
                'pes' => (string) $r['dog_name'],
                'majitel' => (string) $r['owner_name'],
                'odkaz' => $link,
            ], $locale);
            $this->assignments->create($defId, $versionId, (int) $r['dog_id'], (int) $r['owner_id'], $email, $ok ? 'sent' : 'failed');
            $ok ? $result['sent']++ : $result['failed']++;
        }

        AuditService::log($userId, 'research_admin', 'form_broadcast', 'form_definition', (string) $defId, null, $result);
        return $result;
    }
}
