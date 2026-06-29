<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\OwnerRepository;
use App\Services\Auth;

final class PortalController
{
    public function index(): string
    {
        $repo = new OwnerRepository();
        $owner = $repo->findByUserId((int) Auth::id());

        return view('portal/dogs', [
            'title' => 'Moji psi',
            'owner' => $owner,
            'dogs' => $owner !== null ? $repo->dogsOf((int) $owner['id']) : [],
            'emails' => $owner !== null ? $repo->emails((int) $owner['id']) : [],
            'phones' => $owner !== null ? $repo->phones((int) $owner['id']) : [],
        ]);
    }
}
