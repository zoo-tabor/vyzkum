<?php
/** @var array<string, mixed> $response */
/** @var array<int, array<string, mixed>> $answers */
?>
<div class="page-head">
    <h1><?= e($response['form_name']) ?> <span class="muted">v<?= (int) $response['version'] ?></span></h1>
    <p class="muted">
        Pes: <?= e($response['dog_name']) ?> &middot;
        Odesláno: <?= e(\App\Support\Dates::toCzDateTime((string) $response['submitted_at'])) ?>
    </p>
    <p><a href="/portal/forms">&larr; Zpět na dotazníky</a></p>
</div>

<div class="card">
    <table class="table">
        <thead><tr><th style="width:40%">Otázka</th><th>Odpověď</th></tr></thead>
        <tbody>
        <?php foreach ($answers as $a): ?>
            <?php
            $json = !empty($a['value_json']) ? (json_decode((string) $a['value_json'], true) ?: []) : [];
            $fileId = $json['file_id'] ?? null;
            ?>
            <tr>
                <td><?= e($a['label']) ?></td>
                <td>
                    <?php if ($fileId !== null): ?>
                        <a href="/files/<?= (int) $fileId ?>"><?= e($a['value_text'] ?? 'soubor') ?></a>
                    <?php else: ?>
                        <?= nl2br(e((string) ($a['value_text'] ?? ''))) ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if (!empty($response['note'])): ?>
        <p><strong>Poznámka:</strong><br><?= nl2br(e((string) $response['note'])) ?></p>
    <?php endif; ?>
    <p class="muted">Dotazník je jen k nahlédnutí. Pokud potřebujete něco opravit, napište nám ve Zprávách.</p>
</div>
