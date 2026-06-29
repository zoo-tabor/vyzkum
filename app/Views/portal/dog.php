<?php
/** @var array<string, mixed> $dog */
/** @var array<string, mixed>|null $relation */
/** @var array<int, array<string, mixed>> $documents */
/** @var string|null $notice */
/** @var string|null $error */

$isCurrent = $relation !== null && (int) ($relation['is_current'] ?? 0) === 1;
$confirmed = $relation !== null && !empty($relation['confirmed_at']);
$isDead = !empty($dog['death_date']);
?>
<div class="page-head">
    <h1><?= e($dog['name']) ?> <span class="muted">/ <?= e($dog['breed_name']) ?></span></h1>
    <p><a href="/portal">&larr; Zpet na moje psy</a></p>
</div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2>Udaje</h2>
    <table class="table">
        <tr><th style="width:220px">Plemeno</th><td><?= e($dog['breed_name']) ?></td></tr>
        <tr><th>Cislo cipu</th><td><?= e($dog['chip_number'] ?? '') ?></td></tr>
        <tr><th>Datum narozeni</th><td><?= e(\App\Support\Dates::toCz($dog['birth_date'] ?? null)) ?></td></tr>
        <tr><th>Stav</th><td><?= $isDead ? 'uhynuly (' . e(\App\Support\Dates::toCz($dog['death_date'])) . ')' : 'zivy' ?></td></tr>
    </table>
    <p class="muted">Jadrove udaje psa (cip, plemeno, prukaz) muze menit jen vyzkumny tym. Pripadnou opravu napiste v poznamce.</p>
</div>

<?php if ($isCurrent): ?>
    <div class="card">
        <h2>Potvrzeni</h2>
        <?php if ($confirmed): ?>
            <p>Potvrzeno <?= e(\App\Support\Dates::toCz(substr((string) $relation['confirmed_at'], 0, 10))) ?>. Dekujeme.</p>
        <?php else: ?>
            <p>Potvrdte prosim, ze je pes stale vas.</p>
            <form method="post" action="/portal/dogs/<?= (int) $dog['id'] ?>/confirm">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn btn--primary">Pes je stale muj</button>
            </form>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Je pes nazivu?</h2>
        <form method="post" action="/portal/dogs/<?= (int) $dog['id'] ?>/death">
            <?= \App\Core\Csrf::field() ?>
            <label><input type="radio" name="alive" value="yes" <?= $isDead ? '' : 'checked' ?> onclick="document.getElementById('death-block').style.display='none'"> Ano, zije</label>
            <label><input type="radio" name="alive" value="no" <?= $isDead ? 'checked' : '' ?> onclick="document.getElementById('death-block').style.display='block'"> Ne, uhynul</label>

            <div id="death-block" style="display:<?= $isDead ? 'block' : 'none' ?>; margin-top:0.5rem">
                <label for="death_date">Datum umrti (DD.MM.RRRR)</label>
                <input type="text" id="death_date" name="death_date" placeholder="DD.MM.RRRR"
                       value="<?= e(\App\Support\Dates::toCz($dog['death_date'] ?? null)) ?>" style="max-width:200px">
            </div>

            <label for="note">Poznamka (nepovinne)</label>
            <textarea id="note" name="note" rows="2"></textarea>

            <button type="submit" class="btn btn--primary">Ulozit</button>
        </form>
    </div>
<?php endif; ?>

<div class="card">
    <h2>Dotazniky</h2>
    <?php if (!empty($forms)): ?>
        <ul>
            <?php foreach ($forms as $f): ?>
                <li>
                    <?= e($f['name']) ?>
                    <?php if ($isCurrent): ?>
                        - <a href="/portal/dogs/<?= (int) $dog['id'] ?>/forms/<?= (int) $f['definition_id'] ?>">Vyplnit</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="muted">Pro toto plemeno zatim neni publikovany zadny dotaznik.</p>
    <?php endif; ?>

    <?php if (!empty($responses)): ?>
        <h3>Odeslane dotazniky</h3>
        <ul>
            <?php foreach ($responses as $r): ?>
                <li><?= e($r['form_name']) ?> (v<?= (int) $r['version'] ?>) - <?= e(\App\Support\Dates::toCz(substr((string) $r['submitted_at'], 0, 10))) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Zdravotni dokumenty</h2>
    <?php if ($documents === []): ?>
        <p class="muted">Zatim zadne dokumenty.</p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Soubor</th><th>Typ</th><th>Datum</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($documents as $doc): ?>
                <tr>
                    <td><?= e($doc['original_name'] ?? '-') ?></td>
                    <td><?= e($doc['document_type'] ?? '') ?></td>
                    <td><?= e(\App\Support\Dates::toCz($doc['document_date'] ?? null)) ?></td>
                    <td><?php if (!empty($doc['file_id'])): ?><a href="/files/<?= (int) $doc['file_id'] ?>">Stahnout</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($isCurrent): ?>
        <h3>Nahrat dokument</h3>
        <form method="post" action="/portal/dogs/<?= (int) $dog['id'] ?>/document" enctype="multipart/form-data">
            <?= \App\Core\Csrf::field() ?>
            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
            <div class="form-row">
                <div><label for="document_type">Typ dokumentu</label>
                    <input type="text" id="document_type" name="document_type" placeholder="napr. vysetreni, ockovaci prukaz"></div>
                <div><label for="document_date">Datum (DD.MM.RRRR)</label>
                    <input type="text" id="document_date" name="document_date" placeholder="DD.MM.RRRR"></div>
                <div class="form-row__action"><button type="submit" class="btn btn--primary">Nahrat</button></div>
            </div>
        </form>
        <p class="muted">Povoleno PDF, JPG, PNG, WEBP (max 10 MB).</p>
    <?php endif; ?>
</div>
