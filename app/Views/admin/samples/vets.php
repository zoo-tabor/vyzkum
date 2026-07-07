<?php
/** @var array<int, array<string, mixed>> $vets */
/** @var string|null $notice */
/** @var string|null $error */
?>
<div class="page-head"><h1><?= t('Veterináři') ?></h1></div>

<?php if (!empty($notice)): ?><div class="alert alert--ok"><?= e($notice) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>

<div class="card">
    <h2><?= t('Nový veterinář') ?></h2>
    <form method="post" action="/admin/vets" class="form-row">
        <?= \App\Core\Csrf::field() ?>
        <div><label for="name"><?= t('Jméno') ?> *</label><input type="text" id="name" name="name" required></div>
        <div><label for="clinic_name"><?= t('Klinika') ?></label><input type="text" id="clinic_name" name="clinic_name"></div>
        <div><label for="email"><?= t('E-mail') ?></label><input type="email" id="email" name="email"></div>
        <div><label for="phone"><?= t('Telefon') ?></label><input type="text" id="phone" name="phone"></div>
        <div class="form-row__action"><button type="submit" class="btn btn--primary"><?= t('Přidat') ?></button></div>
    </form>
</div>

<div class="card">
    <h2><?= t('Seznam') ?> (<?= count($vets) ?>)</h2>
    <?php if ($vets === []): ?>
        <p class="muted"><?= t('Zatím žádní veterináři.') ?></p>
    <?php else: ?>
        <table class="table">
            <thead><tr><th><?= t('Jméno') ?></th><th><?= t('Klinika') ?></th><th><?= t('E-mail') ?></th><th><?= t('Telefon') ?></th></tr></thead>
            <tbody>
            <?php foreach ($vets as $v): ?>
                <tr>
                    <td><?= e($v['name']) ?></td>
                    <td><?= e($v['clinic_name'] ?? '') ?></td>
                    <td><?= e($v['email'] ?? '') ?></td>
                    <td><?= e($v['phone'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
