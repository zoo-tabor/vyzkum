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
$sexLabel = match ($dog['sex'] ?? 'unknown') { 'male' => 'Pes', 'female' => 'Fena', default => 'Neuvedeno' };
$aliveQuestion = ($dog['sex'] ?? '') === 'female' ? 'Je Vaše fena stále naživu?' : 'Je Váš pes stále naživu?';

// "Vyplneno" = majitel uz potvrdil zivot nebo nahlasil umrti.
$hasInfo = $isDead || !empty($dog['alive_confirmed_at']);
// "Posledni informace z" = kdy byla informace ZADANA (ne datum umrti).
$lastInfoAt = $isDead ? $lastDeathReportAt : ($dog['alive_confirmed_at'] ?? null);

// Formular potvrzeni: Ano (zije - pripadne ozivi ztraceneho psa) / Ne (umrti + pricina).
$renderAliveForm = static function (?string $prefillDeathDate) use ($dogId): void { ?>
    <form method="post" action="/portal/dogs/<?= $dogId ?>/death" class="death-form">
        <?= \App\Core\Csrf::field() ?>
        <p>
            <label class="inline"><input type="radio" name="alive" value="yes" checked data-alive> Ano, žije</label>
            &nbsp;&nbsp;
            <label class="inline"><input type="radio" name="alive" value="no" data-alive> Ne, uhynul(a)</label>
        </p>
        <div class="death-block" hidden>
            <label>Datum úmrtí (DD.MM.RRRR)</label>
            <input type="text" name="death_date" placeholder="DD.MM.RRRR" value="<?= e($prefillDeathDate ?? '') ?>" style="max-width:200px">
            <div class="cause-picker" data-cause-picker>
                <label>Příčina úmrtí</label>
                <div class="cause-levels"></div>
                <input type="hidden" name="death_cause_id" value="">
                <div class="cause-note" hidden>
                    <label>Poznámka (nepovinné)</label>
                    <textarea name="death_cause_note" rows="2"></textarea>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn--primary">Uložit</button>
    </form>
<?php };
?>
<div class="page-head">
    <h1><?= e($dog['name']) ?> <span class="muted">/ <?= e($dog['breed_name']) ?></span></h1>
    <p><a href="/portal">&larr; Zpět na moje psy</a></p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Údaje</h2>
    <table class="table">
        <tr><th style="width:220px">Plemeno</th><td><?= e($dog['breed_name']) ?></td></tr>
        <tr><th>Číslo čipu</th><td><?= e($dog['chip_number'] ?? '') ?: '-' ?></td></tr>
        <tr><th>Číslo průkazu původu</th><td><?= e($dog['pedigree_number'] ?? '') ?: '-' ?></td></tr>
        <tr><th>Pohlaví</th><td><?= e($sexLabel) ?></td></tr>
        <tr><th>Datum narození</th><td><?= e(Dates::toCz($dog['birth_date'] ?? null)) ?: '-' ?></td></tr>
        <tr><th>Barva</th><td><?= e($dog['color'] ?? '') ?: '-' ?></td></tr>
        <?php if ($isDead): ?>
            <tr><th>Datum úmrtí</th><td><?= e(Dates::toCz($dog['death_date'])) ?></td></tr>
        <?php endif; ?>
        <?php if (!empty($dog['death_cause'])): ?>
            <tr><th>Příčina úmrtí</th><td><?= e($dog['death_cause']) ?></td></tr>
        <?php endif; ?>
    </table>
    <p class="muted">Základní údaje psa může měnit jen výzkumný tým. Pro případnou opravu nás kontaktujte přes zprávu.</p>
</div>

<?php if ($isCurrent): ?>
    <div class="card">
        <h2>Potvrzení</h2>
        <?php if (!$hasInfo): ?>
            <p><?= e($aliveQuestion) ?></p>
            <?php $renderAliveForm(null); ?>
        <?php else: ?>
            <p>Poslední informace z <strong><?= e(Dates::toCz(substr((string) $lastInfoAt, 0, 10))) ?></strong>, děkujeme za potvrzení.</p>
            <?php if ($isDead): ?>
                <p class="muted">Pokud jste psa označili omylem (např. ztracený pes se našel), můžete ho níže označit zpět jako živého.</p>
            <?php endif; ?>
            <button type="button" class="btn" data-change-toggle>Změna</button>
            <div class="change-block" hidden style="margin-top:0.75rem">
                <?php $renderAliveForm($isDead ? Dates::toCz($dog['death_date'] ?? null) : null); ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card">
    <h2>Dotazníky</h2>
    <?php if (!empty($forms)): ?>
        <ul>
            <?php foreach ($forms as $f): ?>
                <li>
                    <?= e($f['name']) ?>
                    <?php if ($isCurrent): ?>
                        - <a href="/portal/dogs/<?= $dogId ?>/forms/<?= (int) $f['definition_id'] ?>">Vyplnit</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="muted">Pro toto plemeno zatím není publikovaný žádný dotazník.</p>
    <?php endif; ?>

    <?php if (!empty($responses)): ?>
        <h3>Odeslané dotazníky</h3>
        <ul>
            <?php foreach ($responses as $r): ?>
                <li><a href="/portal/forms/<?= (int) $r['id'] ?>"><?= e($r['form_name']) ?> (v<?= (int) $r['version'] ?>)</a>
                    - <?= e(Dates::toCz(substr((string) $r['submitted_at'], 0, 10))) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Zprávy</h2>
    <?php if ($messageCount > 0): ?>
        <p>K tomuto psovi existuje konverzace (<?= (int) $messageCount ?> zpráv).</p>
        <a class="btn btn--primary" href="/portal/messages/<?= $dogId ?>">Zobrazit konverzaci</a>
    <?php else: ?>
        <p class="muted">Zatím žádná zpráva k tomuto psovi.</p>
        <?php if ($isCurrent): ?>
            <a class="btn btn--primary" href="/portal/messages/<?= $dogId ?>">Napsat zprávu</a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($isCurrent): ?>
    <div class="card">
        <h2>Změna majitele</h2>
        <?php if (!empty($pendingTransfer)): ?>
            <div class="alert alert--ok">Probíhá převod na <strong><?= e($pendingTransfer['new_owner_email']) ?></strong> - čeká na potvrzení novým majitelem.</div>
        <?php else: ?>
            <p class="muted">Pokud psa převádíte na nového majitele, zadejte jeho jméno a e-mail. Novému majiteli přijde odkaz; po jeho potvrzení se vlastnictví automaticky převede.</p>
            <form method="post" action="/portal/dogs/<?= $dogId ?>/transfer">
                <?= \App\Core\Csrf::field() ?>
                <div class="form-row">
                    <div><label for="new_owner_name">Jméno nového majitele</label><input type="text" id="new_owner_name" name="new_owner_name" required></div>
                    <div><label for="new_owner_email">E-mail nového majitele</label><input type="email" id="new_owner_email" name="new_owner_email" required></div>
                    <div class="form-row__action"><button type="submit" class="btn">Nahlásit nového majitele</button></div>
                </div>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card">
    <h2>Dokumenty</h2>
    <p class="muted">Průkaz původu, očkovací průkaz, lékařské zprávy, …</p>
    <?php if ($documents === []): ?>
        <p class="muted">Zatím žádné dokumenty.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Soubor</th><th>Typ</th><th>Datum</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($documents as $doc): ?>
                <tr>
                    <td><?= e($doc['original_name'] ?? '-') ?></td>
                    <td><?= e($doc['document_type'] ?? '') ?></td>
                    <td><?= e(Dates::toCz($doc['document_date'] ?? null)) ?></td>
                    <td><?php if (!empty($doc['file_id'])): ?><a href="/files/<?= (int) $doc['file_id'] ?>">Stáhnout</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($isCurrent): ?>
        <h3>Nahrát dokument</h3>
        <form method="post" action="/portal/dogs/<?= $dogId ?>/document" enctype="multipart/form-data">
            <?= \App\Core\Csrf::field() ?>
            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
            <div class="form-row">
                <div><label for="document_type">Typ dokumentu</label>
                    <input type="text" id="document_type" name="document_type" placeholder="např. průkaz původu, očkovací průkaz"></div>
                <div><label for="document_date">Datum (DD.MM.RRRR)</label>
                    <input type="text" id="document_date" name="document_date" placeholder="DD.MM.RRRR"></div>
                <div class="form-row__action"><button type="submit" class="btn btn--primary">Nahrát</button></div>
            </div>
        </form>
        <p class="muted">Povoleno PDF, JPG, PNG, WEBP (max 10 MB).</p>
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
                alert('Vyberte prosím příčinu úmrtí.');
            }
        });
    });
})();
</script>
