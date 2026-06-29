<?php
/** @var string $transport */
/** @var bool $mailEnabled */
/** @var string $from */
/** @var array{ok:bool, config:array<string,mixed>, steps:array<int,array{step:string,ok:bool,detail:string}>, error:?string}|null $smtp */
/** @var bool $mailFn */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head"><h1>Mailova diagnostika</h1></div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p>
        Transport: <strong><?= $transport === 'smtp' ? 'SMTP' : 'PHP mail()' ?></strong>,
        odesilatel: <code><?= e($from) ?></code>,
        MAIL_ENABLED: <strong><?= $mailEnabled ? 'true' : 'false' ?></strong>
    </p>

    <?php if ($transport === 'smtp' && $smtp !== null): ?>
        <?php if ($smtp['ok']): ?>
            <div class="alert alert--ok">SMTP spojeni i prihlaseni proslo.</div>
        <?php else: ?>
            <div class="alert alert--error">SMTP test neprosel.<?= !empty($smtp['error']) ? ' ' . e($smtp['error']) : '' ?></div>
        <?php endif; ?>
        <table class="table">
            <thead><tr><th>Krok</th><th>Vysledek</th><th>Detail</th></tr></thead>
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
            Pouziva se <strong>PHP mail()</strong> (lokalni MTA hostingu) - vhodne pro wedos, kde je
            odchozi SMTP port 25 blokovan. Funkce mail() je
            <strong><?= $mailFn ? 'dostupna' : 'NEDOSTUPNA' ?></strong>.
            Skutecne odeslani overite testovacim e-mailem nize.
        </p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Poslat testovaci e-mail</h2>
    <?php if (!$mailEnabled): ?>
        <p class="muted">MAIL_ENABLED je false - e-mail se reálně neodešle, jen zaloguje do <code>storage/logs/mail.log</code>.</p>
    <?php endif; ?>
    <form method="post" action="/admin/diagnostics/smtp/send-test">
        <?= \App\Core\Csrf::field() ?>
        <label for="to">E-mail prijemce</label>
        <input type="email" id="to" name="to" required style="max-width:320px" placeholder="vas@email.cz">
        <button type="submit" class="btn btn--primary">Odeslat test</button>
    </form>
    <p class="muted">Stav odeslani se zapise do tabulky <code>email_log</code>.</p>
</div>
