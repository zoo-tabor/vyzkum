<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\BreedRepository;
use App\Repositories\UserRepository;
use App\Services\Auth;
use App\Services\BreedContext;

final class DashboardController
{
    public function index(): string
    {
        $users = new UserRepository();
        $breeds = new BreedRepository();

        return view('admin/dashboard', [
            'title' => 'Dashboard',
            'stats' => [
                'breeds' => $breeds->count(),
                'users' => $users->countByRole(),
                'owners' => $users->countByRole('owner'),
                'clubs' => $users->countByRole('club_viewer'),
            ],
            'currentBreedId' => BreedContext::current(),
            'role' => Auth::role(),
        ]);
    }
}
