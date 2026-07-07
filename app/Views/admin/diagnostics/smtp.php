<?php
/** @var string $transport */
/** @var bool $mailEnabled */
/** @var string $from */
/** @var array{ok:bool, config:array<string,mixed>, steps:array<int,array{step:string,ok:bool,detail:string}>, error:?string}|null $smtp */
/** @var bool $mailFn */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head"><h1><?= t('Mailová diagnostika') ?></h1></div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <p>
        <?= t('Transport: {transport}, odesílatel: {from}, MAIL_ENABLED: {enabled}', [
            'transport' => '<strong>' . ($transport === 'smtp' ? 'SMTP' : 'PHP mail()') . '</strong>',
            'from' => '<code>' . e($from) . '</code>',
            'enabled' => '<strong>' . ($mailEnabled ? 'true' : 'false') . '</strong>',
        ]) ?>
    </p>

    <?php if ($transport === 'smtp' && $smtp !== null): ?>
        <?php if ($smtp['ok']): ?>
            <div class="alert alert--ok"><?= t('SMTP spojení i přihlášení prošlo.') ?></div>
        <?php else: ?>
            <div class="alert alert--error"><?= t('SMTP test neprošel.') ?><?= !empty($smtp['error']) ? ' ' . e($smtp['error']) : '' ?></div>
        <?php endif; ?>
        <table class="table">
            <thead><tr><th><?= t('Krok') ?></th><th><?= t('Výsledek') ?></th><th><?= t('Detail') ?></th></tr></thead>
            <tbody>
            <?php foreach ($smtp['steps'] as $s): ?>
                <tr>
                    <td><?= e($s['step']) ?></td>
                    <td><?= $s['ok'] ? '<span style="color:var(--ok)">OK</span>' : '<span style="color:var(--danger)">' . t('CHYBA') . '</span>' ?></td>
                    <td><code><?= e($s['detail']) ?></code></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>
            <?= t('Používá se {mail} (lokální MTA hostingu) - vhodné pro wedos, kde je odchozí SMTP port 25 blokován. Funkce mail() je {state}. Skutečné odeslání ověříte testovacím e-mailem níže.', [
                'mail' => '<strong>PHP mail()</strong>',
                'state' => '<strong>' . ($mailFn ? t('dostupná') : t('NEDOSTUPNÁ')) . '</strong>',
            ]) ?>
        </p>
    <?php endif; ?>
</div>

<div class="card">
    <h2><?= t('Poslat testovací e-mail') ?></h2>
    <?php if (!$mailEnabled): ?>
        <p class="muted"><?= t('MAIL_ENABLED je false - e-mail se reálně neodešle, jen zaloguje do {file}.', ['file' => '<code>storage/logs/mail.log</code>']) ?></p>
    <?php endif; ?>
    <form method="post" action="/admin/diagnostics/smtp/send-test">
        <?= \App\Core\Csrf::field() ?>
        <label for="to"><?= t('E-mail příjemce') ?></label>
        <input type="email" id="to" name="to" required style="max-width:320px" placeholder="vas@email.cz">
        <button type="submit" class="btn btn--primary"><?= t('Odeslat test') ?></button>
    </form>
    <p class="muted"><?= t('Stav odeslání se zapíše do tabulky {table}.', ['table' => '<code>email_log</code>']) ?></p>
</div>
