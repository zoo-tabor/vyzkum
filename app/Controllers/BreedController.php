<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\BreedRepository;
use App\Repositories\TranslationRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\BreedContext;
use App\Support\Breeds;
use App\Support\I18n;
use App\Support\Policy;

final class BreedController
{
    public function index(): string
    {
        $repo = new BreedRepository();
        return view('admin/breeds', [
            'title' => 'Plemena',
            'breeds' => $repo->all(false),
            'error' => Session::flash('breed_error'),
            'notice' => Session::flash('breed_notice'),
        ]);
    }

    public function create(): string
    {
        Csrf::verify();

        $name = trim((string) input('name'));
        $slugInput = trim((string) input('slug'));
        $slug = self::slugify($slugInput !== '' ? $slugInput : $name);

        $repo = new BreedRepository();

        if ($name === '' || $slug === '') {
            Session::flash('breed_error', t('Vyplňte název plemena.'));
            redirect('/admin/breeds');
        }

        if ($repo->existsSlug($slug)) {
            Session::flash('breed_error', t('Plemeno se slugem "{slug}" už existuje.', ['slug' => $slug]));
            redirect('/admin/breeds');
        }

        $id = $repo->create($slug, $name);
        AuditService::log(Auth::id(), Auth::role(), 'breed_created', 'breed', (string) $id, null, [
            'slug' => $slug,
            'name' => $name,
        ]);

        Session::flash('breed_notice', t('Plemeno "{name}" bylo vytvořeno.', ['name' => $name]));
        redirect('/admin/breeds');
    }

    /** Obrazovka prekladu nazvu plemene (jedno pole 'name' napric vsemi jazyky). */
    public function translations(string $id): string
    {
        $breed = (new BreedRepository())->find((int) $id);
        if ($breed === null) {
            http_response_code(404);
            return view('errors/404', ['title' => 'Plemeno nenalezeno']);
        }

        return view('admin/breeds_translations', [
            'title' => 'Překlady plemene',
            'breed' => $breed,
            'targetLocales' => $this->targetLocales(),
            'existing' => (new TranslationRepository())->localesFor(Breeds::ENTITY, (int) $breed['id'], 'name'),
            'error' => Session::flash('breed_error'),
        ]);
    }

    /** Ulozi preklady nazvu plemene (prazdne pole = smaze -> fallback na cestinu). */
    public function saveTranslations(string $id): string
    {
        Csrf::verify();
        $breed = (new BreedRepository())->find((int) $id);
        if ($breed === null) {
            redirect('/admin/breeds');
        }

        $tx = new TranslationRepository();
        $names = (array) ($_POST['name'] ?? []);
        foreach ($this->targetLocales() as $loc) {
            $tx->set(Breeds::ENTITY, (int) $id, 'name', $loc, (string) ($names[$loc] ?? ''));
        }

        AuditService::log(Auth::id(), Auth::role(), 'breed_translations_saved', 'breed', $id);
        Session::flash('breed_notice', t('Překlady plemene „{name}“ byly uloženy.', ['name' => $breed['name']]));
        redirect('/admin/breeds');
    }

    /** @return array<int, string> cilove jazyky (vse krome zdrojoveho cs) */
    private function targetLocales(): array
    {
        return array_values(array_filter(
            array_keys(I18n::available()),
            static fn (string $l): bool => $l !== I18n::defaultLocale()
        ));
    }

    public function switchContext(): string
    {
        Csrf::verify();

        $raw = (string) input('breed_id');
        if ($raw === '' || $raw === 'all') {
            BreedContext::set(null);
            back('/admin');
        }

        $breedId = (int) $raw;
        $user = Auth::user();
        $accessible = array_map(
            static fn (array $b): int => (int) $b['id'],
            (new BreedRepository())->accessibleFor((int) $user['id'], (string) $user['role'])
        );

        if (!Policy::canAccessBreed($user, $breedId, $accessible)) {
            Session::flash('breed_error', t('K tomuto plemeni nemáte přístup.'));
            back('/admin');
        }

        BreedContext::set($breedId);
        back('/admin');
    }

    private static function slugify(string $value): string
    {
        $value = (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }
}
