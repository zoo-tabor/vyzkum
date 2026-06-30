<?php
/** @var array<string, mixed> $response */
/** @var array<int, array<string, mixed>> $answers */
?>
<div class="page-head">
    <h1>Odpověď: <?= e($response['form_name']) ?> <span class="muted">v<?= (int) $response['version'] ?></span></h1>
    <p class="muted">
        Pes: <?= e($response['dog_name']) ?> &middot;
        Odesláno: <?= e(\App\Support\Dates::toCz(substr((string) $response['submitted_at'], 0, 10))) ?>
    </p>
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
                <td><?= e($a['label']) ?><br><span class="muted"><code><?= e($a['question_key']) ?></code></span></td>
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
</div>
