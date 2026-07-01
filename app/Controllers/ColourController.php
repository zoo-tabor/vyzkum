<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Repositories\BreedRepository;
use App\Repositories\ColourRepository;
use App\Services\Auth;
use App\Services\AuditService;
use App\Services\BreedContext;

final class ColourController
{
    public function index(): string
    {
        $breedId = BreedContext::current();
        return view('admin/colours/index', [
            'title' => 'Barvy',
            'breedId' => $breedId,
            'breeds' => (new BreedRepository())->all(false),
            'colours' => $breedId !== null ? (new ColourRepository())->forBreed($breedId) : [],
            'notice' => Session::flash('colour_notice'),
            'error' => Session::flash('colour_error'),
        ]);
    }

    public function create(): string
    {
        Csrf::verify();
        $breedId = (int) input('breed_id');
        $name = trim((string) input('name'));
        if ($breedId <= 0 || $name === '') {
            Session::flash('colour_error', 'Vyberte plemeno a zadejte název barvy.');
            redirect('/admin/colours');
        }
        (new ColourRepository())->create($breedId, $name);
        AuditService::log(Auth::id(), Auth::role(), 'colour_created', 'breed', (string) $breedId, null, ['name' => $name]);
        Session::flash('colour_notice', 'Barva přidána.');
        redirect('/admin/colours');
    }

    public function delete(string $id): string
    {
        Csrf::verify();
        (new ColourRepository())->delete((int) $id);
        Session::flash('colour_notice', 'Barva odebrána.');
        redirect('/admin/colours');
    }
}
