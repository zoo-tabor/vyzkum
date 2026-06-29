<?php
/** @var array{ok:bool, config:array<string,mixed>, steps:array<int,array{step:string,ok:bool,detail:string}>, error:?string} $result */
/** @var bool $mailEnabled */
?>
<div class="page-head"><h1>Test SMTP</h1></div>

<div class="card">
    <p>
        Konfigurace: <code><?= e($result['config']['host']) ?>:<?= (int) $result['config']['port'] ?></code>,
        STARTTLS: <strong><?= $result['config']['starttls'] ? 'ano' : 'ne' ?></strong>,
        uzivatel: <strong><?= e($result['config']['user']) ?></strong>,
        MAIL_ENABLED: <strong><?= $mailEnabled ? 'true' : 'false' ?></strong>
    </p>

    <?php if ($result['ok']): ?>
        <div class="alert alert--ok">SMTP spojeni i prihlaseni proslo. Mail by mel fungovat.</div>
    <?php else: ?>
        <div class="alert alert--error">
            SMTP test neprosel.<?= !empty($result['error']) ? ' Chyba: ' . e($result['error']) : '' ?>
        </div>
    <?php endif; ?>

    <table class="table">
        <thead><tr><th>Krok</th><th>Vysledek</th><th>Detail</th></tr></thead>
        <tbody>
        <?php foreach ($result['steps'] as $s): ?>
            <tr>
                <td><?= e($s['step']) ?></td>
                <td><?= $s['ok'] ? '<span style="color:var(--ok)">OK</span>' : '<span style="color:var(--danger)">CHYBA</span>' ?></td>
                <td><code><?= e($s['detail']) ?></code></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p class="muted">
        Pokud uvazne uz na kroku <strong>connect</strong> (timeout / odmitnuto), server nedosahne na
        port 25 - typicky blokovany odchozi port 25 na danem hostingu. Reseni: povolit odchozi 25
        u poskytovatele, nebo nechat <code>MAIL_ENABLED=false</code> a brat odkazy z
        <code>storage/logs/mail.log</code> (vyvoj). Mailserver ekospol nabizi jen port 25 (587 je zavreny).
    </p>

    <form method="get" action="/admin/diagnostics/smtp"><button class="btn" type="submit">Spustit test znovu</button></form>
</div>
