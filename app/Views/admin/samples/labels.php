<?php
/** @var array<string, mixed> $batch */
/** @var array<int, array<string, ?string>> $rows */

$labels = [];
foreach ($rows as $row) {
    $labels[] = ['sample_id' => $row['sample_id'], 'role' => 'Veterinar', 'url' => $row['vet_url'] ?? null];
    $labels[] = ['sample_id' => $row['sample_id'], 'role' => 'Majitel', 'url' => $row['owner_url'] ?? null];
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tisk stitku - davka #<?= (int) $batch['id'] ?></title>
    <style>
        :root { --line:#d8dee4; --muted:#6b7280; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, "Segoe UI", Roboto, Arial, sans-serif; background:#f4f6f8; color:#1f2933; }
        .toolbar { display:flex; gap:1rem; align-items:center; justify-content:space-between; padding:1rem 1.5rem; background:#fff; border-bottom:1px solid var(--line); }
        .toolbar a, .toolbar button { font:inherit; padding:0.5rem 1rem; border:1px solid var(--line); border-radius:8px; background:#fff; cursor:pointer; text-decoration:none; color:#1f2933; }
        .toolbar .primary { background:#1f6f43; color:#fff; border-color:#1f6f43; }
        .notice { margin:1rem 1.5rem; padding:0.75rem 1rem; background:#eef4f3; border-radius:8px; color:#13503a; }

        /* Etikety - obrazovkovy nahled */
        .label-page { background:#fff; margin:1rem auto; box-shadow:0 1px 4px rgba(0,0,0,.1); }
        .labels { display:grid; grid-template-columns:repeat(5, 38.1mm); grid-auto-rows:21.2mm; gap:0 3mm; }
        .label-sheet { width:38.1mm; height:21.2mm; padding:1.4mm; overflow:hidden; display:grid; grid-template-columns:17mm 1fr; gap:1.2mm; align-items:center; border:0.2mm dashed #ccc; }
        .label-copy { min-width:0; line-height:1.1; }
        .label-copy strong { display:block; font-family:ui-monospace, Consolas, monospace; font-size:6.2pt; line-height:1.05; overflow-wrap:anywhere; }
        .label-copy span { display:block; margin-top:1.2mm; font-size:6pt; font-weight:700; line-height:1; }
        .label-copy small { display:block; margin-top:1mm; font-size:4.7pt; color:#333; line-height:1.1; }
        .qr-code { width:16.5mm; height:16.5mm; }
        .qr-code canvas, .qr-code img, .qr-code svg { width:16.5mm !important; height:16.5mm !important; }
        .label-missing { font-size:5pt; color:#b42318; }

        @media print {
            @page { size: A4; margin: 0; }
            body { background:#fff; }
            .no-print { display:none !important; }
            .label-page { width:210mm; min-height:297mm; padding:10.7mm 3.75mm; margin:0; box-shadow:none; break-after:page; page-break-after:always; }
            .label-page:last-child { break-after:auto; page-break-after:auto; }
            .label-sheet { border:0; }
        }
    </style>
</head>
<body>
<div class="toolbar no-print">
    <div>
        <strong>Tisk stitku</strong> - davka #<?= (int) $batch['id'] ?><?= !empty($batch['label']) ? ' / ' . e($batch['label']) : '' ?>
    </div>
    <div>
        <button type="button" class="primary" onclick="window.print()">Tisk</button>
        <a href="/admin/batches">Davky</a>
        <a href="/admin/samples">Hotovo</a>
    </div>
</div>

<div class="notice no-print">
    Etikety jsou ve formatu 38,1 &times; 21,2 mm (Avery 65 na A4). V tiskovem dialogu zvolte A4,
    meritko 100 % a okraje "zadne" / bez prizpusobeni strance. QR kody se generuji lokalne v prohlizeci.
</div>

<?php foreach (array_chunk($labels, 65) as $pageLabels): ?>
    <div class="label-page">
        <div class="labels">
            <?php foreach ($pageLabels as $label): ?>
                <article class="label-sheet">
                    <?php if (!empty($label['url'])): ?>
                        <div class="qr-code" data-qr-code="<?= e($label['url']) ?>"></div>
                        <div class="label-copy">
                            <strong><?= e($label['sample_id']) ?></strong>
                            <span><?= e($label['role']) ?></span>
                            <small>Vyzkum plemen psu - Zoo Tabor</small>
                        </div>
                    <?php else: ?>
                        <div class="label-missing">QR odkaz neni dostupny.</div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
<?php endforeach; ?>

<script src="/assets/vendor/qrcode.min.js"></script>
<script>
    document.querySelectorAll('[data-qr-code]').forEach(function (el) {
        new QRCode(el, { text: el.dataset.qrCode, width: 220, height: 220, correctLevel: QRCode.CorrectLevel.M });
    });
</script>
</body>
</html>
