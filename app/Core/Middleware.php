<?php
declare(strict_types=1);

namespace App\Core;

interface Middleware
{
    /**
     * Run before the route handler. To block the request, either redirect()
     * (which exits) or render an error page and exit. Return normally to pass.
     */
    public function handle(Request $request): void;
}
