<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\BreedRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\BreedContext;
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
            Session::flash('breed_error', 'Vyplňte název plemena.');
            redirect('/admin/breeds');
        }

        if ($repo->existsSlug($slug)) {
            Session::flash('breed_error', 'Plemeno se slugem "' . $slug . '" už existuje.');
            redirect('/admin/breeds');
        }

        $id = $repo->create($slug, $name);
        AuditService::log(Auth::id(), Auth::role(), 'breed_created', 'breed', (string) $id, null, [
            'slug' => $slug,
            'name' => $name,
        ]);

        Session::flash('breed_notice', 'Plemeno "' . $name . '" bylo vytvořeno.');
        redirect('/admin/breeds');
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
            Session::flash('breed_error', 'K tomuto plemeni nemáte přístup.');
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
