<?php
/** @var array<string, mixed> $dog */
/** @var array<string, mixed>|null $relation */
/** @var array<int, array<string, mixed>> $documents */
/** @var array<int, array<string, mixed>> $forms */
/** @var array<int, array<string, mixed>> $responses */
/** @var int $messageCount */
/** @var array<int, array<string, mixed>> $causeTree */
/** @var string|null $lastDeathReportAt */
/** @var array<string, mixed>|null $pendingTransfer */
/** @var string|null $notice */
/** @var string|null $error */

use App\Support\Dates;

$dogId = (int) $dog['id'];
$isCurrent = $relation !== null && (int) ($relation['is_current'] ?? 0) === 1;
$isDead = !empty($dog['death_date']);
$sexLabel = match ($dog['sex'] ?? 'unknown') { 'male' => tc('pohlaví', 'Pes'), 'female' => tc('pohlaví', 'Fena'), default => tc('pohlaví', 'Neuvedeno') };
$castrationLabel = match ((string) ($dog['castration_status'] ?? '')) {
    'intact' => t('Nekastrovaný/á'),
    'castrated' => t('Kastrovaný/á'),
    '' => '-',
    default => (string) $dog['castration_status'],
};
$aliveQuestion = ($dog['sex'] ?? '') === 'female' ? t('Je Vaše fena stále naživu?') : t('Je Váš pes stále naživu?');

// "Vyplneno" = majitel uz potvrdil zivot nebo nahlasil umrti.
$hasInfo = $isDead || !empty($dog['alive_confirmed_at']);
// "Posledni informace z" = kdy byla informace ZADANA (ne datum umrti).
$lastInfoAt = $isDead ? $lastDeathReportAt : ($dog['alive_confirmed_at'] ?? null);

// Formular potvrzeni: Ano (zije - pripadne ozivi ztraceneho psa) / Ne (umrti + pricina).
$renderAliveForm = static function (?string $prefillDeathDate) use ($dogId): void { ?>
    <form method="post" action="/portal/dogs/<?= $dogId ?>/death" class="death-form">
        <?= \App\Core\Csrf::field() ?>
        <p>
            <label class="inline"><input type="radio" name="alive" value="yes" checked data-alive> <?= t('Ano, žije') ?></label>
            &nbsp;&nbsp;
            <label class="inline"><input type="radio" name="alive" value="no" data-alive> <?= t('Ne, uhynul(a)') ?></label>
        </p>
        <div class="death-block" hidden>
            <label><?= t('Datum úmrtí (DD.MM.RRRR)') ?></label>
            <input type="text" name="death_date" placeholder="DD.MM.RRRR" value="<?= e($prefillDeathDate ?? '') ?>" style="max-width:200px">
            <div class="cause-picker" data-cause-picker data-placeholder="<?= e(t('– vyberte –')) ?>">
                <label><?= t('Příčina úmrtí') ?></label>
                <div class="cause-levels"></div>
                <input type="hidden" name="death_cause_id" value="">
                <div class="cause-note" hidden>
                    <label><?= t('Poznámka (nepovinné)') ?></label>
                    <textarea name="death_cause_note" rows="2"></textarea>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn--primary"><?= t('Uložit') ?></button>
    </form>
<?php };
?>
<div class="page-head">
    <h1><?= e($dog['name']) ?> <span class="muted">/ <?= e(\App\Support\Breeds::translate($dog['breed_name'])) ?></span></h1>
    <p><a href="/portal">&larr; <?= t('Zpět na moje psy') ?></a></p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2><?= t('Údaje') ?></h2>
    <table class="table">
        <tr><th style="width:220px"><?= t('Plemeno') ?></th><td><?= e(\App\Support\Breeds::translate($dog['breed_name'])) ?></td></tr>
        <tr><th><?= t('Číslo čipu') ?></th><td><?= e($dog['chip_number'] ?? '') ?: '-' ?></td></tr>
        <tr><th><?= t('Číslo průkazu původu') ?></th><td><?= e($dog['pedigree_number'] ?? '') ?: '-' ?></td></tr>
        <tr><th><?= t('Pohlaví') ?></th><td><?= e($sexLabel) ?></td></tr>
        <tr><th><?= t('Datum narození') ?></th><td><?= e(Dates::toCz($dog['birth_date'] ?? null)) ?: '-' ?></td></tr>
        <tr><th><?= t('Barva') ?></th><td><?= e($dog['color'] ?? '') ?: '-' ?></td></tr>
        <tr><th><?= t('Kastrace') ?></th><td><?= e($castrationLabel) ?><?php if (!empty($dog['castration_date'])): ?> (<?= e(Dates::toCz($dog['castration_date'])) ?>)<?php endif; ?></td></tr>
        <?php if ($isDead): ?>
            <tr><th><?= t('Datum úmrtí') ?></th><td><?= e(Dates::toCz($dog['death_date'])) ?></td></tr>
        <?php endif; ?>
        <?php if (!empty($dog['death_cause'])): ?>
            <tr><th><?= t('Příčina úmrtí') ?></th><td><?= e($dog['death_cause']) ?></td></tr>
        <?php endif; ?>
        <?php if (!empty($dog['death_cause_note'])): ?>
            <tr><th><?= t('Poznámka k příčině') ?></th><td><?= e($dog['death_cause_note']) ?></td></tr>
        <?php endif; ?>
    </table>
    <p class="muted"><?= t('Základní údaje psa může měnit jen výzkumný tým. Pro případnou opravu nás kontaktujte přes zprávu.') ?></p>
</div>

<?php if ($isCurrent): ?>
    <div class="card">
        <h2><?= t('Potvrzení') ?></h2>
        <?php if (!$hasInfo): ?>
            <p><?= e($aliveQuestion) ?></p>
            <?php $renderAliveForm(null); ?>
        <?php else: ?>
            <p><?= t('Poslední informace z {date}, děkujeme za potvrzení.', [
                'date' => '<strong>' . e(Dates::toCz(substr((string) $lastInfoAt, 0, 10))) . '</strong>',
            ]) ?></p>
            <?php if ($isDead): ?>
                <p class="muted"><?= t('Pokud jste omylem označili psa za uhynulého (např. ukradený pes, který se později našel), můžete ho níže označit zpět jako živého.') ?></p>
            <?php endif; ?>
            <button type="button" class="btn" data-change-toggle><?= t('Změna') ?></button>
            <div class="change-block" hidden style="margin-top:0.75rem">
                <?php $renderAliveForm($isDead ? Dates::toCz($dog['death_date'] ?? null) : null); ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card">
    <h2><?= t('Dotazníky') ?></h2>
    <?php if (!empty($forms)): ?>
        <ul>
            <?php foreach ($forms as $f): ?>
                <li>
                    <?= e($f['name']) ?>
                    <?php if ($isCurrent): ?>
                        - <a href="/portal/dogs/<?= $dogId ?>/forms/<?= (int) $f['definition_id'] ?>"><?= t('Vyplnit') ?></a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="muted"><?= t('Pro toto plemeno zatím není publikovaný žádný dotazník.') ?></p>
    <?php endif; ?>

    <?php if (!empty($responses)): ?>
        <h3><?= t('Odeslané dotazníky') ?></h3>
        <ul>
            <?php foreach ($responses as $r): ?>
                <li><a href="/portal/forms/<?= (int) $r['id'] ?>"><?= e($r['form_name']) ?> (v<?= (int) $r['version'] ?>)</a>
                    - <?= e(Dates::toCz(substr((string) $r['submitted_at'], 0, 10))) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card">
    <h2><?= t('Zprávy') ?></h2>
    <?php if ($messageCount > 0): ?>
        <p><?= t('K tomuto psovi existuje konverzace ({count} zpráv).', ['count' => (int) $messageCount]) ?></p>
        <a class="btn btn--primary" href="/portal/messages/<?= $dogId ?>"><?= t('Zobrazit konverzaci') ?></a>
    <?php else: ?>
        <p class="muted"><?= t('Zatím žádná zpráva k tomuto psovi.') ?></p>
        <?php if ($isCurrent): ?>
            <a class="btn btn--primary" href="/portal/messages/<?= $dogId ?>"><?= t('Napsat zprávu') ?></a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($isCurrent): ?>
    <div class="card">
        <h2><?= t('Změna majitele') ?></h2>
        <?php if (!empty($pendingTransfer)): ?>
            <div class="alert alert--ok"><?= t('Probíhá převod na {email} - čeká na potvrzení novým majitelem.', [
                'email' => '<strong>' . e($pendingTransfer['new_owner_email']) . '</strong>',
            ]) ?></div>
        <?php else: ?>
            <p class="muted"><?= t('Pokud psa převádíte na nového majitele, zadejte jeho jméno a e-mail. Novému majiteli přijde odkaz; po jeho potvrzení se vlastnictví automaticky převede.') ?></p>
            <form method="post" action="/portal/dogs/<?= $dogId ?>/transfer">
                <?= \App\Core\Csrf::field() ?>
                <div class="form-row">
                    <div><label for="new_owner_name"><?= t('Jméno nového majitele') ?></label><input type="text" id="new_owner_name" name="new_owner_name" required></div>
                    <div><label for="new_owner_email"><?= t('E-mail nového majitele') ?></label><input type="email" id="new_owner_email" name="new_owner_email" required></div>
                    <div class="form-row__action"><button type="submit" class="btn"><?= t('Nahlásit nového majitele') ?></button></div>
                </div>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card">
    <h2><?= t('Dokumenty') ?></h2>
    <p class="muted"><?= t('Průkaz původu, očkovací průkaz, lékařské zprávy, …') ?></p>
    <?php if ($documents === []): ?>
        <p class="muted"><?= t('Zatím žádné dokumenty.') ?></p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th><?= t('Soubor') ?></th><th><?= t('Typ') ?></th><th><?= t('Datum') ?></th><th></th></tr></thead>
            <tbody>
            <?php foreach ($documents as $doc): ?>
                <tr>
                    <td><?= e($doc['original_name'] ?? '-') ?></td>
                    <td><?= e($doc['document_type'] ?? '') ?></td>
                    <td><?= e(Dates::toCz($doc['document_date'] ?? null)) ?></td>
                    <td><?php if (!empty($doc['file_id'])): ?><a href="/files/<?= (int) $doc['file_id'] ?>"><?= t('Stáhnout') ?></a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($isCurrent): ?>
        <h3><?= t('Nahrát dokument') ?></h3>
        <form method="post" action="/portal/dogs/<?= $dogId ?>/document" enctype="multipart/form-data">
            <?= \App\Core\Csrf::field() ?>
            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
            <div class="form-row">
                <div><label for="document_type"><?= t('Typ dokumentu') ?></label>
                    <input type="text" id="document_type" name="document_type" placeholder="<?= e(t('např. průkaz původu, očkovací průkaz')) ?>"></div>
                <div><label for="document_date"><?= t('Datum (DD.MM.RRRR)') ?></label>
                    <input type="text" id="document_date" name="document_date" placeholder="DD.MM.RRRR"></div>
                <div class="form-row__action"><button type="submit" class="btn btn--primary"><?= t('Nahrát') ?></button></div>
            </div>
        </form>
        <p class="muted"><?= t('Povoleno PDF, JPG, PNG, WEBP (max 10 MB).') ?></p>
    <?php endif; ?>
</div>

<?php if ($isCurrent && $causeTree !== []): ?>
<script type="application/json" id="cause-tree"><?= json_encode($causeTree, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<script src="<?= e(asset('assets/cause-picker.js')) ?>"></script>
<?php endif; ?>
<script>
(function () {
    // Prepinac Ano/Ne -> box na datum umrti + pricinu.
    document.querySelectorAll('input[data-alive]').forEach(function (r) {
        r.addEventListener('change', function () {
            var block = document.querySelector('.death-block');
            if (!block) { return; }
            var no = document.querySelector('input[data-alive][value=no]');
            block.hidden = !(no && no.checked);
        });
    });
    // Tlacitko Zmena -> odkryje formular potvrzeni.
    var changeBtn = document.querySelector('[data-change-toggle]');
    if (changeBtn) {
        changeBtn.addEventListener('click', function () {
            var block = document.querySelector('.change-block');
            if (block) { block.hidden = !block.hidden; }
        });
    }
    // Pri hlaseni umrti (Ne) vyzadovat vybranou pricinu.
    document.querySelectorAll('form.death-form').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            var noRadio = f.querySelector('input[name=alive][value=no]');
            var isDeath = noRadio ? noRadio.checked : true;
            if (!isDeath) { return; }
            var picker = f.querySelector('[data-cause-picker]');
            if (picker && picker.querySelector('input[name=death_cause_id]').value === '') {
                e.preventDefault();
                alert(<?= json_encode(t('Vyberte prosím příčinu úmrtí.'), JSON_UNESCAPED_UNICODE) ?>);
            }
        });
    });
})();
</script>
