<?php
/** @var string $content */
/** @var string|null $title */
$user = \App\Services\Auth::user();
$pageTitle = isset($title) ? t($title) . ' - ' . t('Výzkum ZOO Tábor') : t('Výzkum ZOO Tábor');
$isOwner = $user !== null && ($user['role'] ?? '') === 'owner';
$isClub = $user !== null && ($user['role'] ?? '') === 'club_viewer';

$accessibleBreeds = [];
$currentBreedId = null;
if ($user !== null && !$isOwner) {
    $accessibleBreeds = (new \App\Repositories\BreedRepository())
        ->accessibleFor((int) $user['id'], (string) $user['role']);
    $currentBreedId = \App\Services\BreedContext::current();
}

// Upozorneni u "Zprávy": admin = pocet open vlaken, majitel = pocet neprectenych zprav.
$msgBadge = 0;
if ($user !== null) {
    $msgRepo = new \App\Repositories\MessageRepository();
    if ($isOwner) {
        $msgBadge = $msgRepo->countUnreadForOwnerUser((int) $user['id']);
    } elseif (!$isClub) {
        $msgBadge = $msgRepo->countOpenThreads();
    }
}
$badgeHtml = static fn (int $n): string => $n > 0 ? ' <span class="nav-badge">' . $n . '</span>' : '';

$nav = [
    ['/admin', 'Dashboard', true],
    ['/admin/dogs', 'Psi', true],
    ['/admin/owners', 'Majitelé', true],
    ['/admin/samples', 'Vzorky', true],
    ['/admin/forms', 'Formuláře', true],
    ['/admin/health', 'Zdraví', true],
    ['/admin/genetics', 'Genetika', true],
    ['/admin/messages', 'Zprávy', true],
    ['#', 'Statistiky', false],
];
// i18n: menu se renderuje pres t($label) (promenna), extraktor potrebuje literaly.
// t('Dashboard'); t('Psi'); t('Majitelé'); t('Vzorky'); t('Formuláře');
// t('Zdraví'); t('Genetika'); t('Zprávy'); t('Statistiky');
$currentPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="<?= e(\App\Support\I18n::locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="icon" type="image/png" href="/favicon/favicon-96x96.png" sizes="96x96">
    <link rel="icon" type="image/svg+xml" href="/favicon/favicon.svg">
    <link rel="shortcut icon" href="/favicon/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/favicon/site.webmanifest">
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
</head>
<body>
<?php if ($user !== null && $isOwner): ?>
    <header class="topbar">
        <button type="button" class="nav-toggle" aria-label="Menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>
        <div class="topbar__brand"><a href="/portal"><img class="topbar__logo" src="/favicon/favicon.svg" width="28" height="28" alt=""> <?= t('Výzkum <span>ZOO Tábor</span>') ?></a></div>
        <div class="topbar__user">
            <?php include ROOT_PATH . '/app/Views/partials/lang_switch.php'; ?>
            <span class="topbar__email"><?= e($user['email']) ?></span>
            <form method="post" action="/logout" class="inline">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn btn--ghost"><?= t('Odhlásit') ?></button>
            </form>
        </div>
    </header>
    <?php
    $portalActive = static fn (string $href): string => match (true) {
        $href === '/portal' => ($currentPath === '/portal' || str_starts_with((string) $currentPath, '/portal/dogs')) ? 'active' : '',
        default => str_starts_with((string) $currentPath, $href) ? 'active' : '',
    };
    ?>
    <div class="shell">
        <nav class="sidebar" id="sidebar">
            <ul>
                <li><a href="/portal" class="<?= $portalActive('/portal') ?>"><?= t('Moji psi') ?></a></li>
                <li><a href="/portal/forms" class="<?= $portalActive('/portal/forms') ?>"><?= t('Dotazníky') ?></a></li>
                <li><a href="/portal/messages" class="<?= $portalActive('/portal/messages') ?>"><?= t('Zprávy') ?><?= $badgeHtml($msgBadge) ?></a></li>
                <li><a href="/portal/contacts" class="<?= $portalActive('/portal/contacts') ?>"><?= t('Moje údaje') ?></a></li>
                <li><a href="/portal/settings" class="<?= $portalActive('/portal/settings') ?>"><?= t('Nastavení') ?></a></li>
            </ul>
        </nav>
        <main class="content"><?= $content ?></main>
    </div>
<?php elseif ($user !== null && $isClub): ?>
    <header class="topbar">
        <button type="button" class="nav-toggle" aria-label="Menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>
        <div class="topbar__brand"><a href="/club"><img class="topbar__logo" src="/favicon/favicon.svg" width="28" height="28" alt=""> <?= t('Výzkum <span>ZOO Tábor</span>') ?></a></div>
        <div class="topbar__user">
            <?php include ROOT_PATH . '/app/Views/partials/lang_switch.php'; ?>
            <span class="topbar__email"><?= e($user['email']) ?></span>
            <span class="topbar__role"><?= t('klub') ?></span>
            <form method="post" action="/logout" class="inline">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn btn--ghost"><?= t('Odhlásit') ?></button>
            </form>
        </div>
    </header>
    <div class="shell">
        <nav class="sidebar" id="sidebar">
            <ul>
                <li><a href="/club" class="<?= $currentPath === '/club' ? 'active' : '' ?>"><?= t('Přehled') ?></a></li>
                <li><a href="/club/dogs" class="<?= $currentPath === '/club/dogs' ? 'active' : '' ?>"><?= t('Psi') ?></a></li>
            </ul>
        </nav>
        <main class="content"><?= $content ?></main>
    </div>
<?php elseif ($user !== null): ?>
    <header class="topbar">
        <button type="button" class="nav-toggle" aria-label="Menu" aria-controls="sidebar" aria-expanded="false">&#9776;</button>
        <div class="topbar__brand">
            <a href="/admin"><img class="topbar__logo" src="/favicon/favicon.svg" width="28" height="28" alt=""> <?= t('Výzkum <span>ZOO Tábor</span>') ?></a>
        </div>

        <form class="breed-switch" method="post" action="/admin/breed-context">
            <?= \App\Core\Csrf::field() ?>
            <label for="breed_id"><?= t('Plemeno:') ?></label>
            <select id="breed_id" name="breed_id" onchange="this.form.submit()">
                <option value="all"<?= $currentBreedId === null ? ' selected' : '' ?>><?= t('Všechna plemena') ?></option>
                <?php foreach ($accessibleBreeds as $breed): ?>
                    <option value="<?= (int) $breed['id'] ?>"<?= $currentBreedId === (int) $breed['id'] ? ' selected' : '' ?>>
                        <?= e(\App\Support\Breeds::translate($breed['name'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit"><?= t('Přepnout') ?></button></noscript>
        </form>

        <div class="topbar__user">
            <?php include ROOT_PATH . '/app/Views/partials/lang_switch.php'; ?>
            <span class="topbar__email"><?= e($user['email']) ?></span>
            <span class="topbar__role"><?= e($user['role']) ?></span>
            <form method="post" action="/logout" class="inline">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn btn--ghost"><?= t('Odhlásit') ?></button>
            </form>
        </div>
    </header>

    <div class="shell">
        <nav class="sidebar" id="sidebar">
            <ul>
                <?php foreach ($nav as [$href, $label, $enabled]): ?>
                    <?php
                    $active = $href !== '#' && (
                        $currentPath === $href
                        || ($href !== '/admin' && is_string($currentPath) && str_starts_with($currentPath, $href . '/'))
                    );
                    ?>
                    <li>
                        <a href="<?= e($href) ?>"
                           class="<?= $active ? 'active' : '' ?> <?= $enabled ? '' : 'disabled' ?>"
                           <?= $enabled ? '' : 'title="' . e(t('Připravuje se v další fázi')) . '"' ?>>
                            <?= e(t($label)) ?><?= $href === '/admin/messages' ? $badgeHtml($msgBadge) : '' ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <?php if (($user['role'] ?? '') === 'research_admin'): ?>
                    <li class="sidebar__section"><?= t('Nastavení') ?></li>
                    <li><a href="/admin/breeds" class="<?= $currentPath === '/admin/breeds' ? 'active' : '' ?>"><?= t('Plemena') ?></a></li>
                    <li><a href="/admin/colours" class="<?= str_starts_with((string) $currentPath, '/admin/colours') ? 'active' : '' ?>"><?= t('Barvy') ?></a></li>
                    <li><a href="/admin/clubs" class="<?= str_starts_with((string) $currentPath, '/admin/clubs') ? 'active' : '' ?>"><?= t('Kluby') ?></a></li>
                    <li><a href="/admin/import" class="<?= str_starts_with((string) $currentPath, '/admin/import') ? 'active' : '' ?>"><?= t('Import CSV') ?></a></li>
                    <li><a href="/admin/email-templates" class="<?= str_starts_with((string) $currentPath, '/admin/email-templates') ? 'active' : '' ?>"><?= t('Šablony e-mailů') ?></a></li>
                    <li><a href="/admin/security" class="<?= $currentPath === '/admin/security' ? 'active' : '' ?>"><?= t('Zabezpečení') ?></a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <main class="content">
            <?= $content ?>
        </main>
    </div>
<?php else: ?>
    <main class="auth">
        <div class="auth-inner">
            <?php include ROOT_PATH . '/app/Views/partials/lang_switch.php'; ?>
            <?= $content ?>
        </div>
    </main>
<?php endif; ?>

<?php if ($user !== null): ?>
<div class="nav-overlay"></div>
<script>
(function () {
    var toggle = document.querySelector('.nav-toggle');
    var overlay = document.querySelector('.nav-overlay');
    if (!toggle) { return; }
    function setOpen(open) {
        document.body.classList.toggle('nav-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    toggle.addEventListener('click', function () {
        setOpen(!document.body.classList.contains('nav-open'));
    });
    if (overlay) { overlay.addEventListener('click', function () { setOpen(false); }); }
    document.querySelectorAll('.sidebar a').forEach(function (a) {
        a.addEventListener('click', function () { setOpen(false); });
    });
    window.addEventListener('resize', function () {
        if (window.innerWidth > 860) { setOpen(false); }
    });
})();
</script>
<?php endif; ?>
</body>
</html>
