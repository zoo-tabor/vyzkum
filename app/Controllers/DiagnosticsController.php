<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Session;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\MailService;

final class DiagnosticsController
{
    public function smtp(): string
    {
        $cfg = Config::instance();
        $transport = strtolower((string) $cfg->get('MAIL_TRANSPORT', 'mail'));

        return view('admin/diagnostics/smtp', [
            'title' => 'Mailová diagnostika',
            'transport' => $transport,
            'mailEnabled' => (bool) $cfg->get('MAIL_ENABLED', false),
            'from' => (string) $cfg->get('SMTP_FROM', ''),
            'smtp' => $transport === 'smtp' ? MailService::diagnose() : null,
            'mailFn' => function_exists('mail'),
            'notice' => Session::flash('mail_test_notice'),
            'error' => Session::flash('mail_test_error'),
        ]);
    }

    public function sendTest(): string
    {
        Csrf::verify();

        $to = trim((string) input('to'));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Session::flash('mail_test_error', t('Zadejte platný e-mail.'));
            redirect('/admin/diagnostics/smtp');
        }

        $ok = MailService::send(
            $to,
            'Test e-mail - Výzkum ZOO Tábor',
            "Toto je testovací e-mail z CRM Výzkum ZOO Tábor.\nČas: " . date('Y-m-d H:i:s') . "\n",
            'test'
        );
        AuditService::log(Auth::id(), Auth::role(), 'mail_test', 'email', $to, null, ['ok' => $ok]);

        if ($ok) {
            Session::flash('mail_test_notice', t('Pokus o odeslání na {to} proběhl. Stav najdete v email_log (při MAIL_ENABLED=false se jen zalogoval do mail.log).', ['to' => $to]));
        } else {
            Session::flash('mail_test_error', t('Odeslání selhalo - viz email_log / storage/logs/mail.log.'));
        }
        redirect('/admin/diagnostics/smtp');
    }
}
