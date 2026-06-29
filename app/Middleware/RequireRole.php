<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Services\Auth;
use App\Support\Policy;

final class RequireRole implements Middleware
{
    /** @var array<int, string> */
    private array $roles;

    public function __construct(string ...$roles)
    {
        $this->roles = $roles;
    }

    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            redirect('/login');
        }

        if (!Policy::hasRole(Auth::user(), $this->roles)) {
            http_response_code(403);
            echo view('errors/403', ['title' => 'Pristup odepren']);
            exit;
        }
    }
}
