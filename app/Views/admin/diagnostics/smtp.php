<?php
/** @var string $transport */
/** @var bool $mailEnabled */
/** @var string $from */
/** @var array{ok:bool, config:array<string,mixed>, steps:array<int,array{step:string,ok:bool,detail:string}>, error:?string}|null $smtp */
/** @var bool $mailFn */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head"><h1>Mailová diagnostika</h1></div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p>
        Transport: <strong><?= $transport === 'smtp' ? 'SMTP' : 'PHP mail()' ?></strong>,
        odesílatel: <code><?= e($from) ?></code>,
        MAIL_ENABLED: <strong><?= $mailEnabled ? 'true' : 'false' ?></strong>
    </p>

    <?php if ($transport === 'smtp' && $smtp !== null): ?>
        <?php if ($smtp['ok']): ?>
            <div class="alert alert--ok">SMTP spojení i přihlášení prošlo.</div>
        <?php else: ?>
            <div class="alert alert--error">SMTP test neprošel.<?= !empty($smtp['error']) ? ' ' . e($smtp['error']) : '' ?></div>
        <?php endif; ?>
        <table class="table">
            <thead><tr><th>Krok</th><th>Výsledek</th><th>Detail</th></tr></thead>
            <tbody>
            <?php foreach ($smtp['steps'] as $s): ?>
                <tr>
                    <td><?= e($s['step']) ?></td>
                    <td><?= $s['ok'] ? '<span style="color:var(--ok)">OK</span>' : '<span style="color:var(--danger)">CHYBA</span>' ?></td>
                    <td><code><?= e($s['detail']) ?></code></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>
            Používá se <strong>PHP mail()</strong> (lokální MTA hostingu) - vhodné pro wedos, kde je
            odchozí SMTP port 25 blokován. Funkce mail() je
            <strong><?= $mailFn ? 'dostupná' : 'NEDOSTUPNÁ' ?></strong>.
            Skutečné odeslání ověříte testovacím e-mailem níže.
        </p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Poslat testovací e-mail</h2>
    <?php if (!$mailEnabled): ?>
        <p class="muted">MAIL_ENABLED je false - e-mail se reálně neodešle, jen zaloguje do <code>storage/logs/mail.log</code>.</p>
    <?php endif; ?>
    <form method="post" action="/admin/diagnostics/smtp/send-test">
        <?= \App\Core\Csrf::field() ?>
        <label for="to">E-mail příjemce</label>
        <input type="email" id="to" name="to" required style="max-width:320px" placeholder="vas@email.cz">
        <button type="submit" class="btn btn--primary">Odeslat test</button>
    </form>
    <p class="muted">Stav odeslání se zapíše do tabulky <code>email_log</code>.</p>
</div>
