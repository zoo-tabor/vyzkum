<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Services\Auth;

final class RequireAuth implements Middleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            redirect('/login');
        }
    }
}
