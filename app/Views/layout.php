<?php
/** @var string $content */
/** @var string|null $title */
$user = \App\Services\Auth::user();
$pageTitle = isset($title) ? $title . ' - Výzkum ZOO Tábor' : 'Výzkum ZOO Tábor';
$isOwner = $user !== null && ($user['role'] ?? '') === 'owner';
$isClub = $user !== null && ($user['role'] ?? '') === 'club_viewer';

$accessibleBreeds = [];
$currentBreedId = null;
if ($user !== null && !$isOwner) {
    $accessibleBreeds = (new \App\Repositories\BreedRepository())
        ->accessibleFor((int) $user['id'], (string) $user['role']);
    $currentBreedId = \App\Services\BreedContext::current();
}

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
$currentPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="cs">
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
        <div class="topbar__brand"><a href="/portal"><img class="topbar__logo" src="/favicon/favicon.svg" alt=""> Výzkum <span>ZOO Tábor</span></a></div>
        <div class="topbar__user">
            <span class="topbar__email"><?= e($user['email']) ?></span>
            <form method="post" action="/logout" class="inline">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn btn--ghost">Odhlásit</button>
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
        <nav class="sidebar">
            <ul>
                <li><a href="/portal" class="<?= $portalActive('/portal') ?>">Moji psi</a></li>
                <li><a href="/portal/messages" class="<?= $portalActive('/portal/messages') ?>">Zprávy</a></li>
                <li><a href="/portal/contacts" class="<?= $portalActive('/portal/contacts') ?>">Moje údaje</a></li>
                <li><a href="/portal/settings" class="<?= $portalActive('/portal/settings') ?>">Nastavení</a></li>
            </ul>
        </nav>
        <main class="content"><?= $content ?></main>
    </div>
<?php elseif ($user !== null && $isClub): ?>
    <header class="topbar">
        <div class="topbar__brand"><a href="/club"><img class="topbar__logo" src="/favicon/favicon.svg" alt=""> Výzkum <span>ZOO Tábor</span></a></div>
        <div class="topbar__user">
            <span class="topbar__email"><?= e($user['email']) ?></span>
            <span class="topbar__role">klub</span>
            <form method="post" action="/logout" class="inline">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn btn--ghost">Odhlásit</button>
            </form>
        </div>
    </header>
    <div class="shell">
        <nav class="sidebar">
            <ul>
                <li><a href="/club" class="<?= $currentPath === '/club' ? 'active' : '' ?>">Přehled</a></li>
                <li><a href="/club/dogs" class="<?= $currentPath === '/club/dogs' ? 'active' : '' ?>">Psi</a></li>
            </ul>
        </nav>
        <main class="content"><?= $content ?></main>
    </div>
<?php elseif ($user !== null): ?>
    <header class="topbar">
        <div class="topbar__brand">
            <a href="/admin"><img class="topbar__logo" src="/favicon/favicon.svg" alt=""> Výzkum <span>ZOO Tábor</span></a>
        </div>

        <form class="breed-switch" method="post" action="/admin/breed-context">
            <?= \App\Core\Csrf::field() ?>
            <label for="breed_id">Plemeno:</label>
            <select id="breed_id" name="breed_id" onchange="this.form.submit()">
                <option value="all"<?= $currentBreedId === null ? ' selected' : '' ?>>Všechna plemena</option>
                <?php foreach ($accessibleBreeds as $breed): ?>
                    <option value="<?= (int) $breed['id'] ?>"<?= $currentBreedId === (int) $breed['id'] ? ' selected' : '' ?>>
                        <?= e($breed['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit">Přepnout</button></noscript>
        </form>

        <div class="topbar__user">
            <span class="topbar__email"><?= e($user['email']) ?></span>
            <span class="topbar__role"><?= e($user['role']) ?></span>
            <form method="post" action="/logout" class="inline">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn btn--ghost">Odhlásit</button>
            </form>
        </div>
    </header>

    <div class="shell">
        <nav class="sidebar">
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
                           <?= $enabled ? '' : 'title="Připravuje se v další fázi"' ?>>
                            <?= e($label) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <?php if (($user['role'] ?? '') === 'research_admin'): ?>
                    <li class="sidebar__section">Nastavení</li>
                    <li><a href="/admin/breeds" class="<?= $currentPath === '/admin/breeds' ? 'active' : '' ?>">Plemena</a></li>
                    <li><a href="/admin/colours" class="<?= str_starts_with((string) $currentPath, '/admin/colours') ? 'active' : '' ?>">Barvy</a></li>
                    <li><a href="/admin/clubs" class="<?= str_starts_with((string) $currentPath, '/admin/clubs') ? 'active' : '' ?>">Kluby</a></li>
                    <li><a href="/admin/import" class="<?= str_starts_with((string) $currentPath, '/admin/import') ? 'active' : '' ?>">Import CSV</a></li>
                    <li><a href="/admin/security" class="<?= $currentPath === '/admin/security' ? 'active' : '' ?>">Zabezpečení</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <main class="content">
            <?= $content ?>
        </main>
    </div>
<?php else: ?>
    <main class="auth">
        <?= $content ?>
    </main>
<?php endif; ?>
</body>
</html>
