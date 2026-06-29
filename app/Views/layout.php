<?php
/** @var string $content */
/** @var string|null $title */
$user = \App\Services\Auth::user();
$pageTitle = isset($title) ? $title . ' - Vyzkum Zoo Tabor' : 'Vyzkum Zoo Tabor';
$isOwner = $user !== null && ($user['role'] ?? '') === 'owner';

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
    ['/admin/owners', 'Majitele', true],
    ['/admin/samples', 'Vzorky', true],
    ['/admin/forms', 'Formulare', true],
    ['#', 'Zdravi', false],
    ['#', 'Genetika', false],
    ['#', 'Zpravy', false],
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
    <link rel="stylesheet" href="<?= e(asset('assets/app.css')) ?>">
</head>
<body>
<?php if ($user !== null && $isOwner): ?>
    <header class="topbar">
        <div class="topbar__brand"><a href="/portal">Vyzkum <span>Zoo Tabor</span></a></div>
        <div class="topbar__user">
            <span class="topbar__email"><?= e($user['email']) ?></span>
            <form method="post" action="/logout" class="inline">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn btn--ghost">Odhlasit</button>
            </form>
        </div>
    </header>
    <main class="content" style="max-width:900px; margin:0 auto;">
        <?= $content ?>
    </main>
<?php elseif ($user !== null): ?>
    <header class="topbar">
        <div class="topbar__brand">
            <a href="/admin">Vyzkum <span>Zoo Tabor</span></a>
        </div>

        <form class="breed-switch" method="post" action="/admin/breed-context">
            <?= \App\Core\Csrf::field() ?>
            <label for="breed_id">Plemeno:</label>
            <select id="breed_id" name="breed_id" onchange="this.form.submit()">
                <option value="all"<?= $currentBreedId === null ? ' selected' : '' ?>>Vsechna plemena</option>
                <?php foreach ($accessibleBreeds as $breed): ?>
                    <option value="<?= (int) $breed['id'] ?>"<?= $currentBreedId === (int) $breed['id'] ? ' selected' : '' ?>>
                        <?= e($breed['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit">Prepnout</button></noscript>
        </form>

        <div class="topbar__user">
            <span class="topbar__email"><?= e($user['email']) ?></span>
            <span class="topbar__role"><?= e($user['role']) ?></span>
            <form method="post" action="/logout" class="inline">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="btn btn--ghost">Odhlasit</button>
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
                           <?= $enabled ? '' : 'title="Pripravuje se v dalsi fazi"' ?>>
                            <?= e($label) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <?php if (($user['role'] ?? '') === 'research_admin'): ?>
                    <li class="sidebar__section">Nastaveni</li>
                    <li><a href="/admin/breeds" class="<?= $currentPath === '/admin/breeds' ? 'active' : '' ?>">Plemena</a></li>
                    <li><a href="/admin/import" class="<?= str_starts_with((string) $currentPath, '/admin/import') ? 'active' : '' ?>">Import CSV</a></li>
                    <li><a href="/admin/security" class="<?= $currentPath === '/admin/security' ? 'active' : '' ?>">Zabezpeceni</a></li>
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
