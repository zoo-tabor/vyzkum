<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Services\MailService;

final class DiagnosticsController
{
    public function smtp(): string
    {
        return view('admin/diagnostics/smtp', [
            'title' => 'Test SMTP',
            'result' => MailService::diagnose(),
            'mailEnabled' => (bool) Config::instance()->get('MAIL_ENABLED', false),
        ]);
    }
}
