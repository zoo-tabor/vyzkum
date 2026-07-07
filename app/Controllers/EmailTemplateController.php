<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\EmailTemplateRepository;
use App\Repositories\TranslationRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Support\I18n;

/**
 * Sprava sablon transakcnich e-mailu (Nastaveni -> Sablony e-mailu). Cesky zdroj
 * (subject/body) editovatelny + preklady per jazyk na jedne obrazovce. Rozeslani
 * pak jde dle jazyka prijemce (MailTemplateService).
 */
final class EmailTemplateController
{
    public function index(): string
    {
        return view('admin/email_templates/index', [
            'title' => 'Šablony e-mailů',
            'templates' => (new EmailTemplateRepository())->all(),
            'notice' => Session::flash('email_tpl_notice'),
            'error' => Session::flash('email_tpl_error'),
        ]);
    }

    public function edit(string $key): string
    {
        $tpl = (new EmailTemplateRepository())->find($key);
        if ($tpl === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Šablona nenalezena']);
        }

        $tx = new TranslationRepository();
        return view('admin/email_templates/edit', [
            'title' => 'Šablona e-mailu',
            'tpl' => $tpl,
            'targetLocales' => $this->targetLocales(),
            'subjectTx' => $tx->localesFor(EmailTemplateRepository::ENTITY, (int) $tpl['id'], 'subject'),
            'bodyTx' => $tx->localesFor(EmailTemplateRepository::ENTITY, (int) $tpl['id'], 'body'),
            'error' => Session::flash('email_tpl_error'),
        ]);
    }

    public function save(string $key): string
    {
        Csrf::verify();
        $repo = new EmailTemplateRepository();
        $tpl = $repo->find($key);
        if ($tpl === null) {
            redirect('/admin/email-templates');
        }

        // Cesky zdroj.
        $subject = trim((string) input('subject'));
        $body = trim((string) input('body'));
        if ($subject === '' || $body === '') {
            Session::flash('email_tpl_error', t('Vyplňte předmět i text (český zdroj).'));
            redirect('/admin/email-templates/' . rawurlencode($key));
        }
        $repo->update($key, $subject, $body);

        // Preklady (prazdne pole = smaze -> fallback na cestinu).
        $tx = new TranslationRepository();
        $subjTr = (array) ($_POST['subject_tr'] ?? []);
        $bodyTr = (array) ($_POST['body_tr'] ?? []);
        foreach ($this->targetLocales() as $loc) {
            $tx->set(EmailTemplateRepository::ENTITY, (int) $tpl['id'], 'subject', $loc, (string) ($subjTr[$loc] ?? ''));
            $tx->set(EmailTemplateRepository::ENTITY, (int) $tpl['id'], 'body', $loc, (string) ($bodyTr[$loc] ?? ''));
        }

        AuditService::log(Auth::id(), Auth::role(), 'email_template_saved', 'email_template', $key);
        Session::flash('email_tpl_notice', t('Šablona uložena.'));
        redirect('/admin/email-templates/' . rawurlencode($key));
    }

    /** @return array<int, string> cilove jazyky (vse krome zdrojoveho cs) */
    private function targetLocales(): array
    {
        return array_values(array_filter(
            array_keys(I18n::available()),
            static fn (string $l): bool => $l !== I18n::defaultLocale()
        ));
    }
}
